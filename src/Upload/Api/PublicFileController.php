<?php
// src/Upload/Api/PublicFileController.php

namespace App\Upload\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Контроллер для обслуживания публичных загруженных файлов.
 * Используется в dev-режиме, в production файлы должны отдаваться напрямую через Nginx/Apache.
 */
class PublicFileController extends AbstractController
{
    /**
     * Отдать публичный файл из директории uploads.
     * 
     * GET /uploads/{path}
     * Например: /uploads/2025/11/avatars/filename.png
     */
    #[Route('/uploads/{path}', name: 'public_file_serve', requirements: ['path' => '.+'], methods: ['GET'])]
    public function servePublicFile(string $path): Response
    {
        // Путь к публичной директории
        $publicDir = $this->getParameter('kernel.project_dir') . '/public';
        $filePath = $publicDir . '/uploads/' . $path;

        // Проверка безопасности: файл должен быть внутри uploads
        $realPath = realpath($filePath);
        $uploadsDir = realpath($publicDir . '/uploads');

        if (!$realPath || !$uploadsDir || !str_starts_with($realPath, $uploadsDir)) {
            return new Response('File not found', 404);
        }

        // Проверка существования файла
        if (!file_exists($realPath) || !is_file($realPath)) {
            return new Response('File not found', 404);
        }

        // Определение MIME-типа
        $mimeType = mime_content_type($realPath) ?: 'application/octet-stream';

        // Отдача файла
        return new BinaryFileResponse(
            $realPath,
            200,
            [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=31536000', // Кэш на год
            ]
        );
    }
}
