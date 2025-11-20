<?php
// src/Document/Api/DocumentController.php

namespace App\Document\Api;

use App\Document\Service\DocumentService;
use App\User\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Все роуты здесь защищены файрволом 'api' (требуют JWT)
#[Route('/api/documents')]
class DocumentController extends AbstractController
{
    public function __construct(
        private readonly DocumentService $documentService
    ) {
    }

    /**
     * Эндпоинт "Загрузить Файл" (Шаг 1).
     *
     * Фронтенд отправляет запрос (multipart/form-data)
     * с двумя полями:
     * 1. 'file' (сам файл)
     * 2. 'docType' (строка, e.g., 'chat_file' или 'ustav')
     */
    #[Route('/upload', methods: ['POST'])]
    public function upload(
        Request $request,
        #[CurrentUser] User $user // Текущий пользователь из JWT
    ): JsonResponse {

        // 1. Получаем файл из запроса
        /** @var ?UploadedFile $file */
        $file = $request->files->get('file');

        // 2. Получаем тип документа из POST-данных
        $docType = $request->request->get('docType');

        if (!$file) {
            return $this->json(['error' => 'Файл не найден (ожидается поле "file")'], 400);
        }
        if (!$docType) {
            return $this->json(['error' => 'Тип документа не указан (ожидается поле "docType")'], 400);
        }

        // 3. Передаем в сервис
        // Вся "грязная" работа (S3, БД, генерация имени)
        // спрятана внутри DocumentService.
        try {
            $document = $this->documentService->uploadFile($file, $user, $docType);
        } catch (\Exception $e) {
            // (ApiExceptionListener поймает это, если это наш кастомный Exception)
            return $this->json(['error' => $e->getMessage()], 500);
        }

        // 4. Возвращаем ID, который фронтенд прикрепит к сообщению в чате
        return $this->json([
            'document_id' => $document->getId(),
            'file_name' => $document->getFileName(),
            'file_path' => $document->getFilePath(),
        ], Response::HTTP_CREATED); // 201 Created
    }

    /**
     * Эндпоинт для безопасного скачивания файла.
     * GET /api/documents/{id}/download
     */
    #[Route('/{id}/download', methods: ['GET'])]
    public function download(
        int $id,
        #[CurrentUser] User $user
    ): Response {

        try {
            $fileData = $this->documentService->downloadFile($id, $user);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }

        // Создаем StreamedResponse - это экономит память PHP.
        // Файл не грузится в RAM целиком, а отдается кусочками.
        $response = new StreamedResponse(function () use ($fileData) {
            $outputStream = fopen('php://output', 'wb');
            stream_copy_to_stream($fileData['stream'], $outputStream);
        });

        // Устанавливаем заголовки для браузера
        $response->headers->set('Content-Type', $fileData['mimeType']);
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                'attachment', // или 'inline', если хотите открывать в браузере
                $fileData['filename']
            )
        );

        return $response;
    }

    /**
     * Эндпоинт "Заменить документ".
     * POST /api/documents/{id}/replace
     */
    #[Route('/{id}/replace', methods: ['POST'])]
    public function replace(
        int $id,
        Request $request,
        #[CurrentUser] User $user
    ): JsonResponse {
        $file = $request->files->get('file');
        $reason = $request->request->get('reason');

        if (!$file) {
            return $this->json(['error' => 'Файл не найден'], 400);
        }

        try {
            $newDoc = $this->documentService->replaceDocument($id, $file, $user, $reason);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }

        return $this->json([
            'document_id' => $newDoc->getId(),
            'file_name' => $newDoc->getFileName(),
            'status' => 'success'
        ], 201);
    }
}
