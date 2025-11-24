<?php
// src/Upload/Api/UploadController.php

namespace App\Upload\Api;

use App\Upload\Service\UploadService;
use App\Upload\Enum\FileContext;
use App\User\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/uploads', name: 'upload_')]
class UploadController extends AbstractController
{
    public function __construct(
        private readonly UploadService $uploadService
    ) {
    }

    /**
     * Загрузить файл.
     * 
     * POST /api/uploads
     * Body: file (multipart), context (avatar|user_document|etc)
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function upload(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        try {
            $file = $request->files->get('file');
            $contextString = $request->request->get('context');

            if (!$file) {
                return new JsonResponse(['error' => 'Файл не загружен'], 400);
            }

            if (!$contextString) {
                return new JsonResponse(['error' => 'Не указан context'], 400);
            }

            // Преобразуем строку в FileContext enum
            $context = FileContext::from($contextString);

            // Правильный порядок: $file, $user, $context
            $uploadedFile = $this->uploadService->uploadFile($file, $user, $context);

            return new JsonResponse([
                'id' => $uploadedFile->getId(),
                'url' => '/uploads/' . $uploadedFile->getStoragePath() . '/' . $uploadedFile->getStoredFileName(),
                'file_name' => $uploadedFile->getOriginalFileName(),
                'size' => $uploadedFile->getFileSize(),
                'context' => $uploadedFile->getContext()->value,
                'is_temporary' => true,
                'expires_at' => (new \DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s')
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Ошибка загрузки файла'], 500);
        }
    }

    /**
     * Получить список файлов пользователя.
     * 
     * GET /api/uploads?context=user_document
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $contextString = $request->query->get('context');
        $context = $contextString ? FileContext::from($contextString) : null;

        $files = $this->uploadService->getUserFiles($user, $context);

        $data = array_map(function ($file) {
            return [
                'id' => $file->getId(),
                'file_name' => $file->getOriginalFileName(),
                'size' => $file->getFileSize(),
                'context' => $file->getContext()->value,
                'mime_type' => $file->getMimeType(),
                'created_at' => $file->getCreatedAt()->format('Y-m-d H:i:s'),
                'url' => '/uploads/' . $file->getStoragePath() . '/' . $file->getStoredFileName()
            ];
        }, $files);

        return new JsonResponse($data);
    }

    /**
     * Скачать файл.
     * 
     * GET /api/uploads/{id}/download
     */
    #[Route('/{id}/download', name: 'download', methods: ['GET'])]
    public function download(int $id, #[CurrentUser] User $user): Response
    {
        try {
            $uploadedFile = $this->uploadService->getFile($id);

            // Проверка прав доступа (только владелец может скачать)
            if ($uploadedFile->getUploadedBy()->getId() !== $user->getId()) {
                return new JsonResponse(['error' => 'Доступ запрещён'], 403);
            }

            $filePath = $this->uploadService->getFilePath($uploadedFile);

            return new BinaryFileResponse(
                $filePath,
                200,
                [
                    'Content-Type' => $uploadedFile->getMimeType(),
                    'Content-Disposition' => 'attachment; filename="' . $uploadedFile->getOriginalFileName() . '"'
                ]
            );

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Файл не найден'], 404);
        }
    }

    /**
     * Заменить файл.
     * 
     * POST /api/uploads/{id}/replace
     */
    #[Route('/{id}/replace', name: 'replace', methods: ['POST'])]
    public function replace(int $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        try {
            $file = $request->files->get('file');

            if (!$file) {
                return new JsonResponse(['error' => 'Файл не загружен'], 400);
            }

            $oldFile = $this->uploadService->getFile($id);

            // Проверка прав доступа
            if ($oldFile->getUploadedBy()->getId() !== $user->getId()) {
                return new JsonResponse(['error' => 'Доступ запрещён'], 403);
            }

            $newFile = $this->uploadService->replaceFile($oldFile, $file);

            return new JsonResponse([
                'id' => $newFile->getId(),
                'file_name' => $newFile->getOriginalFileName(),
                'message' => 'Файл успешно заменён'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Удалить файл.
     * 
     * DELETE /api/uploads/{id}
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, #[CurrentUser] User $user): JsonResponse
    {
        try {
            $file = $this->uploadService->getFile($id);

            // Проверка прав доступа
            if ($file->getUploadedBy()->getId() !== $user->getId()) {
                return new JsonResponse(['error' => 'Доступ запрещён'], 403);
            }

            $this->uploadService->deleteFile($file);

            return new JsonResponse(['message' => 'Файл удалён']);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Файл не найден'], 404);
        }
    }

    /**
     * Подтвердить использование файла.
     * 
     * POST /api/uploads/{id}/confirm
     */
    #[Route('/{id}/confirm', name: 'confirm', methods: ['POST'])]
    public function confirm(int $id, #[CurrentUser] User $user): JsonResponse
    {
        try {
            $file = $this->uploadService->getFile($id);

            // Проверка прав доступа
            if ($file->getUploadedBy()->getId() !== $user->getId()) {
                return new JsonResponse(['error' => 'Доступ запрещён'], 403);
            }

            // Подтверждаем файл
            $file->setConfirmed(true);
            $this->uploadService->save($file);

            return new JsonResponse([
                'message' => 'Файл подтверждён',
                'confirmed_at' => $file->getConfirmedAt()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Файл не найден'], 404);
        }
    }
}
