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

class DocumentService
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly FilesystemOperator $defaultStorage,
        private readonly SluggerInterface $slugger,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * "Шаг 1": Обрабатывает загрузку файла и создает запись в БД.
     */
    public function uploadFile(UploadedFile $file, User $uploader, string $docType): Document
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        $path = sprintf(
            'documents/user_%d/%s/%s',
            $uploader->getId(),
            (new \DateTime())->format('Y-m'),
            $newFilename
        );

        try {
            // 4. ФИЗИЧЕСКИ СОХРАНЯЕМ ФАЙЛ
            $tempPath = $file->getRealPath();
            $stream = fopen($tempPath, 'r');

            $this->defaultStorage->writeStream(
                $path,
                $stream
            );

            if (is_resource($stream)) {
                fclose($stream);
            }

        } catch (\Exception $e) {
            $this->logger->error('Ошибка загрузки файла в хранилище', [
                'error' => $e->getMessage(),
                'path' => $path,
            ]);
            throw new \Exception('Не удалось сохранить файл.');
        }

        // 5. СОЗДАЕМ ЗАПИСЬ В БД
        $document = new Document();
        $document->setUploaderUser($uploader);
        $document->setDocType($docType);
        $document->setFileName($file->getClientOriginalName());
        $document->setFilePath($path);

        // --- ЗАПОЛНЯЕМ НОВЫЕ ПОЛЯ (Размер и Тип) ---
        $document->setFileSize($file->getSize()); // Размер в байтах
        $document->setMimeType($file->getMimeType()); // e.g. application/pdf

        // Статус по умолчанию 'pending' уже задан в Entity

        $this->documentRepository->save($document, true);

        $this->logger->info('Файл успешно загружен', [
            'doc_id' => $document->getId(),
            'user_id' => $uploader->getId(),
            'path' => $path,
            'size' => $document->getFileSize()
        ]);

        return $document;
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
}
