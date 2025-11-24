<?php
// src/Upload/Service/UploadService.php

namespace App\Upload\Service;

use App\Upload\Entity\UploadedFile;
use App\Upload\Enum\FileContext;
use App\Upload\Repository\UploadRepository;
use App\Upload\Exception\InvalidFileTypeException;
use App\Upload\Exception\FileTooLargeException;
use App\Upload\Exception\FileNotFoundException;
use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\File\UploadedFile as HttpUploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;

/**
 * Унифицированный сервис для работы с загрузкой файлов.
 * Абстрагирует физическое хранение от бизнес-логики.
 */
class UploadService
{
    public function __construct(
        #[Target('public.storage')]
        private readonly FilesystemOperator $publicStorage,
        #[Target('default.storage')]
        private readonly FilesystemOperator $privateStorage,
        private readonly UploadRepository $uploadRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger,
        private readonly LoggerInterface $logger
    ) {
    }

    public function uploadFile(
        HttpUploadedFile $file,
        User $user,
        FileContext $context,
        ?string $contextId = null,
        ?string $description = null
    ): UploadedFile {
        // 1. Валидация
        $this->validateFile($file, $context);

        // 2. Генерация имени
        $storedFileName = $this->generateUniqueFileName($file);

        // 3. Определение пути (например: uploads/2025/11/avatars)
        $storagePath = $this->getStoragePath($context);
        $fullPath = $storagePath . '/' . $storedFileName;

        // 4. Выбор хранилища (Публичное или Приватное)
        $isPublic = $context->isPublic();
        $storage = $isPublic ? $this->publicStorage : $this->privateStorage;

        try {
            $stream = fopen($file->getRealPath(), 'r');
            // Пишем в выбранное хранилище
            $storage->writeStream($fullPath, $stream);
            
            if (is_resource($stream)) {
                fclose($stream);
            }
        } catch (FilesystemException $e) {
            $this->logger->error('Ошибка загрузки файла', [
                'file' => $file->getClientOriginalName(),
                'storage' => $isPublic ? 'public' : 'private',
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Не удалось сохранить файл');
        }

        // 5. Запись в БД
        $uploadedFile = new UploadedFile();
        $uploadedFile->setUploadedBy($user);
        $uploadedFile->setContext($context);
        $uploadedFile->setContextId($contextId);
        $uploadedFile->setOriginalFileName($file->getClientOriginalName());
        $uploadedFile->setStoredFileName($storedFileName);
        $uploadedFile->setStoragePath($storagePath);
        $uploadedFile->setFileSize($file->getSize());
        $uploadedFile->setMimeType($file->getMimeType() ?? 'application/octet-stream');
        $uploadedFile->setDescription($description);
        
        // (Опционально) Если у вас есть поле is_public в БД, раскомментируйте:
        // $uploadedFile->setIsPublic($isPublic);

        $this->uploadRepository->save($uploadedFile);

        return $uploadedFile;
    }

    /**
     * Получить список файлов пользователя.
     */
    public function getUserFiles(User $user, ?FileContext $context = null): array
    {
        return $this->uploadRepository->findByUser($user, $context);
    }

    /**
     * Получить файл по ID.
     */
    public function getFile(int $fileId): UploadedFile
    {
        $file = $this->uploadRepository->findById($fileId);

        if (!$file) {
            throw new FileNotFoundException('Файл не найден');
        }

        return $file;
    }

    /**
     * Скачать файл.
     */
    public function downloadFile(int $fileId, User $user): array
    {
        $file = $this->getFile($fileId);
        
        // Определяем, откуда читать файл
        $isPublic = $file->getContext()->isPublic();
        $storage = $isPublic ? $this->publicStorage : $this->privateStorage;

        // Формируем полный путь (путь папки + имя файла)
        $fullPath = $file->getStoragePath() . '/' . $file->getStoredFileName();

        try {
            if (!$storage->fileExists($fullPath)) {
                 throw new FileNotFoundException('Файл физически отсутствует на диске');
            }
            $stream = $storage->readStream($fullPath);
        } catch (FilesystemException $e) {
            $this->logger->error('Ошибка чтения файла', ['id' => $fileId, 'error' => $e->getMessage()]);
            throw new FileNotFoundException('Ошибка чтения файла');
        }

        return [
            'stream' => $stream,
            'filename' => $file->getOriginalFileName(),
            'mimeType' => $file->getMimeType(),
        ];
    }

    /**
     * Удалить файл.
     */
    public function deleteFile(UploadedFile $file): void
    {
        // Мягкое удаление
        $file->setDeleted(true);
        $this->entityManager->flush();

        $this->logger->info('Файл удален', [
            'file_id' => $file->getId()
        ]);
    }

    /**
     * Заменить файл новым.
     */
    public function replaceFile(
        UploadedFile $oldFile,
        HttpUploadedFile $newFile
    ): UploadedFile {
        // Загружаем новый файл
        $user = $oldFile->getUploadedBy();
        $newUploadedFile = $this->uploadFile(
            $newFile,
            $user,
            $oldFile->getContext(),
            $oldFile->getContextId(),
            'Замена файла'
        );

        // Помечаем старый как удалённый
        $oldFile->setDeleted(true);
        $this->entityManager->flush();

        $this->logger->info('Файл заменен', [
            'old_file_id' => $oldFile->getId(),
            'new_file_id' => $newUploadedFile->getId()
        ]);

        return $newUploadedFile;
    }

    /**
     * Валидация файла.
     */
    private function validateFile(HttpUploadedFile $file, FileContext $context): void
    {
        $maxSize = $context->getMaxFileSize();
        if ($file->getSize() > $maxSize) {
            throw new FileTooLargeException(
                sprintf('Файл слишком большой. Максимальный размер: %d MB', $maxSize / 1024 / 1024)
            );
        }

        $allowedMimeTypes = $context->getAllowedMimeTypes();
        $fileMimeType = $file->getMimeType();

        if (!in_array($fileMimeType, $allowedMimeTypes, true)) {
            throw new InvalidFileTypeException(
                sprintf('Недопустимый тип файла: %s. Разрешены: %s', 
                    $fileMimeType, 
                    implode(', ', $allowedMimeTypes)
                )
            );
        }
    }

    /**
     * Генерация уникального имени файла.
     */
    private function generateUniqueFileName(HttpUploadedFile $file): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalName);
        $extension = $file->guessExtension() ?? 'bin';

        return sprintf(
            '%s-%s.%s',
            $safeFilename,
            uniqid('', true),
            $extension
        );
    }

    /**
     * Определение пути хранения в зависимости от контекста.
     */
    private function getStoragePath(FileContext $context): string
    {
        $year = date('Y');
        $month = date('m');

        return match($context) {
            FileContext::DOCUMENT => "{$year}/{$month}/documents",
            FileContext::AVATAR => "{$year}/{$month}/avatars",
            FileContext::CONTRACT_ATTACHMENT => "{$year}/{$month}/contracts",
            FileContext::CHAT_ATTACHMENT => "{$year}/{$month}/chat",
            FileContext::MESSAGE_ATTACHMENT => "{$year}/{$month}/messages",
            default => "{$year}/{$month}/other",
        };
    }

    /**
     * Сохранить файл в базу данных.
     */
    public function save(UploadedFile $file): void
    {
        $this->uploadRepository->save($file);
    }

    /**
     * Получить физический путь к файлу.
     */
    public function getFilePath(UploadedFile $file): string
    {
        $isPublic = $file->getContext()->isPublic();
        
        // Полный путь внутри хранилища (например: 2025/11/avatars/filename.jpg)
        $fullPath = $file->getStoragePath() . '/' . $file->getStoredFileName();
        
        // Для локального хранилища возвращаем абсолютный путь
        if ($isPublic) {
            // Публичное хранилище: /public/uploads/...
            return dirname(__DIR__, 3) . '/public/uploads/' . $fullPath;
        } else {
            // Приватное хранилище: /var/storage/...
            return dirname(__DIR__, 3) . '/var/storage/' . $fullPath;
        }
    }
}
