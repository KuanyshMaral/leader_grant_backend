<?php
// src/Document/Service/DocumentService.php

namespace App\Document\Service;

use App\Document\Entity\Document;
use App\Document\Repository\DocumentRepository;
use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator; // Абстракция для S3/Minio/Local
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Document\Exception\DocumentNotFoundException;         // <-- ДОБАВИТЬ
use App\Document\Exception\DocumentAccessDeniedException;    // <-- ДОБАВИТЬ
use App\Document\Exception\DocumentAlreadyLinkedException; // <-- ДОБАВИТЬ

class DocumentService
{
    // Мы "внедряем" не S3, а абстрактный FilesystemOperator.
    // Это позволяет завтра "переехать" с S3 на Minio,
    // просто поменяв 1 строчку в конфиге.
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly FilesystemOperator $defaultStorage, // (Flysystem)
        private readonly SluggerInterface $slugger,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * "Шаг 1": Обрабатывает загрузку файла и создает "временную"
     * (непривязанную) запись в БД.
     *
     * @param string $docType - Тип документа (e.g., 'ustav', 'inn_scan', 'chat_file')
     */
    public function uploadFile(UploadedFile $file, User $uploader, string $docType): Document
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        // 1. Делаем имя файла безопасным (e.g., "Скан ИНН!.pdf" -> "skan-inn.pdf")
        $safeFilename = $this->slugger->slug($originalFilename);

        // 2. Генерируем уникальное имя, чтобы избежать конфликтов
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        // 3. Генерируем путь в S3 (e.g., "documents/user_5/2025-11/skan-inn-1a2b3c.pdf")
        $path = sprintf(
            'documents/user_%d/%s/%s',
            $uploader->getId(),
            (new \DateTime())->format('Y-m'),
            $newFilename
        );

        try {
            // 4. ФИЗИЧЕСКИ СОХРАНЯЕМ ФАЙЛ
            // (Flysystem/writeStream ожидает 'resource', а не 'UploadedFile')

            // 4a. Получаем реальный путь к временному файлу
            $tempPath = $file->getRealPath();

            // 4b. Открываем его как "поток" (stream) для чтения ('r')
            $stream = fopen($tempPath, 'r');

            // 4c. Передаем "поток" в хранилище (Flysystem)
            $this->defaultStorage->writeStream(
                $path,
                $stream
            );

            // 4d. (Важно!) Закрываем "поток"
            if (is_resource($stream)) {
                fclose($stream);
            }

        } catch (\Exception $e) {
            $this->logger->error('Ошибка загрузки файла в хранилище', [
                'error' => $e->getMessage(),
                'path' => $path,
            ]);
            // (Здесь должно быть кастомное ExternalStorageException)
            throw new \Exception('Не удалось сохранить файл.');
        }

        // 5. СОЗДАЕМ ЗАПИСЬ В БД
        $document = new Document();
        $document->setUploaderUser($uploader);
        $document->setDocType($docType);
        $document->setFileName($file->getClientOriginalName()); // Сохраняем "красивое" имя
        $document->setFilePath($path); // Сохраняем "S3" путь

        // (Поля company, application, message пока остаются NULL)

        $this->documentRepository->save($document, true); // (flush: true)

        $this->logger->info('Файл успешно загружен', [
            'doc_id' => $document->getId(),
            'user_id' => $uploader->getId(),
            'path' => $path,
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
            // [РЕАЛИЗОВАНО]
            throw new DocumentNotFoundException();
        }

        // 1. Проверяем, что этот User (Агент/Клиент) сам же и загрузил этот файл
        if ($document->getUploaderUser()->getId() !== $user->getId()) {
            $this->logger->warning('Попытка привязать чужой документ', [
                'doc_id' => $documentId, 'user_id' => $user->getId()
            ]);
            // [РЕАЛИЗОВАНО]
            throw new DocumentAccessDeniedException();
        }

        // 2. Проверяем, что файл не был "использован" (привязан) ранее
        if ($document->isLinked()) { // <-- Теперь этот метод существует
            $this->logger->warning('Попытка повторно привязать документ', [
                'doc_id' => $documentId,
            ]);
            // [РЕАЛИЗОВАНО]
            throw new DocumentAlreadyLinkedException();
        }

        return $document;
    }
}
