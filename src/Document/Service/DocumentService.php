<?php
// src/Document/Service/DocumentService.php

namespace App\Document\Service;

use App\Document\Entity\Document;
use App\Document\Repository\DocumentRepository;
use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Document\Exception\DocumentNotFoundException;
use App\Document\Exception\DocumentAccessDeniedException;
use App\Document\Exception\DocumentAlreadyLinkedException;
use Symfony\Component\Validator\Validator\ValidatorInterface; // <-- Валидатор
use Symfony\Component\Validator\Constraints as Assert; // <-- Правила

class DocumentService
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly FilesystemOperator $defaultStorage,
        private readonly SluggerInterface $slugger,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * "Шаг 1": Обрабатывает загрузку файла и создает запись в БД.
     */
    public function uploadFile(UploadedFile $file, User $uploader, string $docType): Document
    {
        // 1. ВАЛИДАЦИЯ (Бизнес-правила)
        $errors = $this->validator->validate($file, [
            new Assert\File([
                'maxSize' => '15M',
                'mimeTypes' => [
                    'application/pdf',
                    'application/x-pdf',
                    'image/jpeg',
                    'image/png',
                    'application/msword', // .doc
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
                    'application/vnd.ms-excel', // .xls
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                ],
                'mimeTypesMessage' => 'Пожалуйста, загрузите допустимый документ (PDF, JPG, PNG, Word, Excel)',
            ])
        ]);

        if (count($errors) > 0) {
            throw new \InvalidArgumentException($errors[0]->getMessage());
        }

        // 2. ГЕНЕРАЦИЯ ИМЕНИ
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        // Добавляем uniqid, чтобы имена не пересекались
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        // 3. ПУТЬ ХРАНЕНИЯ (Структура: user_ID/YYYY/MM/file)
        $path = sprintf(
            'user_%d/%s/%s',
            $uploader->getId(),
            (new \DateTime())->format('Y/m'),
            $newFilename
        );

        // 4. СОХРАНЕНИЕ НА ДИСК (Через Stream для экономии памяти)
        $stream = fopen($file->getRealPath(), 'r');
        try {
            $this->defaultStorage->writeStream($path, $stream);
        } catch (FilesystemException $e) {
            throw new \Exception('Ошибка записи файла на диск: ' . $e->getMessage());
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        // 5. СОХРАНЕНИЕ В БД
        $document = new Document();
        $document->setUploaderUser($uploader);
        $document->setDocType($docType);
        $document->setFileName($file->getClientOriginalName());
        $document->setFilePath($path);
        $document->setFileSize($file->getSize());
        $document->setMimeType($file->getMimeType());

        $this->documentRepository->save($document, true);

        $this->logger->info('Файл загружен', ['id' => $document->getId(), 'path' => $path]);

        return $document;
    }

    /**
     * Метод для СКАЧИВАНИЯ файла (возвращает поток).
     */
    public function downloadFile(int $documentId, User $user): array
    {
        $document = $this->findAndVerify($documentId, $user);

        try {
            // Получаем поток (resource) из хранилища
            $stream = $this->defaultStorage->readStream($document->getFilePath());
        } catch (FilesystemException $e) {
            throw new \Exception('Файл физически не найден на диске.');
        }

        return [
            'stream' => $stream,
            'filename' => $document->getFileName(),
            'mimeType' => $document->getMimeType()
        ];
    }

    /**
     * "Шаг 2": Проверяет, что User имеет право привязать этот
     * документ и что он еще не был привязан.
     */
    public function findAndVerify(int $documentId, User $user): Document
    {
        $document = $this->documentRepository->find($documentId);

        if (!$document) {
            throw new DocumentNotFoundException();
        }

        // 1. Проверяем, что этот User сам же и загрузил этот файл
        if ($document->getUploaderUser()->getId() !== $user->getId()) {
            $this->logger->warning('Попытка привязать чужой документ', [
                'doc_id' => $documentId, 'user_id' => $user->getId()
            ]);
            throw new DocumentAccessDeniedException();
        }

        // 2. Проверяем, что файл не был "использован" (привязан) ранее
        if ($document->isLinked()) {
            $this->logger->warning('Попытка повторно привязать документ', [
                'doc_id' => $documentId,
            ]);
            throw new DocumentAlreadyLinkedException();
        }

        return $document;
    }

    /**
     * Замена документа (Версионность).
     * Старый документ помечается как архивный, создается новый со ссылкой на старый.
     */
    public function replaceDocument(int $oldDocId, UploadedFile $file, User $uploader, ?string $reason): Document
    {
        // 1. Находим старый документ
        $oldDoc = $this->findAndVerify($oldDocId, $uploader);

        // 2. Загружаем новый файл как обычно
        $newDoc = $this->uploadFile($file, $uploader, $oldDoc->getDocType());

        // 3. Связываем их и архивируем старый
        $newDoc->setParentDocument($oldDoc);
        $newDoc->setVersionComment($reason);

        // Переносим привязки (чтобы новый документ встал на место старого)
        $newDoc->setCompany($oldDoc->getCompany());
        $newDoc->setApplication($oldDoc->getApplication());

        $oldDoc->setArchived(true);

        $this->documentRepository->save($oldDoc);
        $this->documentRepository->save($newDoc, true);

        return $newDoc;
    }
}
