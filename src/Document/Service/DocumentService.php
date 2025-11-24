<?php
// src/Document/Service/DocumentService.php

namespace App\Document\Service;

use App\Document\Entity\Document;
use App\Document\Repository\DocumentRepository;
use App\User\Entity\User;
use App\Upload\Service\UploadService;
use App\Upload\Enum\FileContext;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Document\Exception\DocumentNotFoundException;
use App\Document\Exception\DocumentAccessDeniedException;
use App\Document\Exception\DocumentAlreadyLinkedException;
use App\Upload\Enum\FileStorage;
use App\Document\Enum\DocumentType;

use App\Document\Enum\DocumentStatus;
use App\User\Enum\UserRole;

class DocumentService
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly UploadService $uploadService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Загрузка документа. Использует UploadService для хранения файла.
     */
    public function uploadFile(UploadedFile $file, User $uploader, string $docType, ?\App\Application\Entity\Application $application = null): Document
    {
        // 1. Загружаем файл через UploadService
        $uploadedFile = $this->uploadService->uploadFile(
            $file,
            $uploader,
            FileContext::DOCUMENT,
            null,  // contextId будет установлен позже, при привязке к Company
            "Документ типа: {$docType}"
        );

        // 2. Создаём метаданные документа
        $document = new Document();
        $document->setDocType(DocumentType::from($docType));
        $document->setFile($uploadedFile);
        $document->setUploaderUser($uploader);
        $document->setStatus(DocumentStatus::PENDING);
        
        if ($application) {
            $document->setApplication($application);
            // Если документ привязан к заявке, можно сразу привязать и компанию клиента
            $document->setCompany($application->getClientUser()->getCompany());
        }

        // 3. Сохраняем
        $this->documentRepository->save($document, true);

        $this->logger->info('Document created', [
            'document_id' => $document->getId(),
            'doc_type' => $docType,
            'uploader_id' => $uploader->getId()
        ]);

        return $document;
    }

    /**
     * Скачивание документа. Делегируем в UploadService.
     */
    public function downloadFile(int $documentId, User $user): array
    {
        $document = $this->findAndCheckAccess($documentId, $user);

        if (!$document->getFile()) {
            throw new DocumentNotFoundException('Файл не привязан к документу');
        }

        // Делегируем скачивание в UploadService
        return $this->uploadService->downloadFile($document->getFile()->getId(), $user);
    }

    /**
     * Проверка прав доступа к документу.
     */
    /**
     * Проверка прав доступа к документу (без проверки на привязку).
     */
    private function findAndCheckAccess(int $documentId, User $user): Document
    {
        $document = $this->documentRepository->find($documentId);

        if (!$document) {
            throw new DocumentNotFoundException();
        }

        if ($document->isDeleted()) {
            throw new DocumentNotFoundException();
        }

        // Разрешаем агентам управлять всеми документами (временное решение, нужен ACL)
        if ($user->getRole() === UserRole::AGENT) {
            $this->logger->info('Агент управляет документом', [
                'agent_id' => $user->getId(),
                'doc_id' => $documentId,
                'uploader_id' => $document->getUploaderUser()->getId()
            ]);
            return $document;
        }

        // Проверяем права доступа для обычных пользователей
        if ($document->getUploaderUser()->getId() !== $user->getId()) {
            $this->logger->warning('Попытка доступа к чужому документу', [
                'doc_id' => $documentId,
                'user_id' => $user->getId()
            ]);
            throw new DocumentAccessDeniedException();
        }

        return $document;
    }

    /**
     * Проверка прав доступа к документу и проверка на отсутствие привязки.
     * Используется для операций, которые требуют непривязанного документа (удаление, привязка).
     */
    public function findAndVerify(int $documentId, User $user): Document
    {
        $document = $this->findAndCheckAccess($documentId, $user);

        // Проверка на повторное использование (если нужно)
        if ($document->isLinked()) {
            $this->logger->warning('Попытка повторно привязать документ', [
                'doc_id' => $documentId
            ]);
            throw new DocumentAlreadyLinkedException();
        }

        return $document;
    }

    /**
     * Замена документа. Использует UploadService для замены файла.
     */
    public function replaceDocument(int $oldDocId, UploadedFile $file, User $uploader, ?string $reason): Document
    {
        $oldDoc = $this->findAndCheckAccess($oldDocId, $uploader);

        if (!$oldDoc->getFile()) {
             // Если старого файла нет, просто загружаем новый как обычный файл
             $newUploadedFile = $this->uploadService->uploadFile(
                $file,
                $uploader,
                FileContext::DOCUMENT,
                null,
                "Замена документа (предыдущий файл отсутствовал)"
            );
        } else {
            // 1. Заменяем файл через UploadService
            $newUploadedFile = $this->uploadService->replaceFile(
                $oldDoc->getFile(),
                $file
            );
        }

        // 2. Создаём новый Document
        $newDoc = new Document();
        $newDoc->setDocType($oldDoc->getDocType());
        $newDoc->setFile($newUploadedFile);
        $newDoc->setUploaderUser($uploader);
        $newDoc->setCompany($oldDoc->getCompany());
        $newDoc->setApplication($oldDoc->getApplication());
        $newDoc->setMessage($oldDoc->getMessage());
        $newDoc->setStatus(DocumentStatus::PENDING);
        $newDoc->setParentDocument($oldDoc);
        $newDoc->setVersionComment($reason);

        // 3. Помечаем старый как архивный
        $oldDoc->setArchived(true);

        $this->entityManager->persist($newDoc);
        $this->entityManager->flush();

        $this->logger->info('Document replaced', [
            'old_doc_id' => $oldDocId,
            'new_doc_id' => $newDoc->getId(),
            'reason' => $reason
        ]);

        return $newDoc;
    }

    /**
     * Удаление документа. Делегируем удаление файла в UploadService.
     */
    public function deleteDocument(int $documentId, User $user): void
    {
        $document = $this->findAndCheckAccess($documentId, $user);

        if ($document->getFile()) {
            // Удаляем файл через UploadService
            $this->uploadService->deleteFile($document->getFile());
        }

        // Удаляем запись документа
        $this->documentRepository->remove($document, true);

        $this->logger->info('Document deleted', [
            'document_id' => $documentId,
            'user_id' => $user->getId()
        ]);
    }

    /**
     * Одобрить документ (модерация).
     */
    public function approveDocument(int $documentId, User $moderator): Document
    {
        $document = $this->documentRepository->find($documentId);

        if (!$document) {
            throw new DocumentNotFoundException();
        }

        $document->setStatus('approved');
        $this->documentRepository->save($document, true);

        $this->logger->info('Document approved', [
            'document_id' => $documentId,
            'moderator_id' => $moderator->getId()
        ]);

        return $document;
    }

    /**
     * Отклонить документ (модерация).
     */
    public function rejectDocument(int $documentId, User $moderator, string $reason): Document
    {
        $document = $this->documentRepository->find($documentId);

        if (!$document) {
            throw new DocumentNotFoundException();
        }

        $document->setStatus('rejected');
        $document->setRejectionReason($reason);
        $this->documentRepository->save($document, true);

        $this->logger->info('Document rejected', [
            'document_id' => $documentId,
            'moderator_id' => $moderator->getId(),
            'reason' => $reason
        ]);

        return $document;
    }

    /**
     * Получить документы на модерации.
     */
    public function getPendingDocuments(): array
    {
        return $this->documentRepository->findBy(['status' => 'pending']);
    }
}
