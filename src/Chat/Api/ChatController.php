<?php
// src/Chat/Api/ChatController.php

namespace App\Chat\Api;

use App\Chat\DTO\SendMessageDTO;
use App\Chat\Service\ChatService;
use App\User\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

// Все роуты здесь защищены файрволом 'api' (требуют JWT)
#[Route('/api')]
class ChatController extends AbstractController
{
    public function __construct(
        private readonly ChatService $chatService
    ) {
    }

    /**
     * Эндпоинт "Получить историю чата"
     * (для Клиента, Агента, Партнера, Админа)
     *
     * GET /api/applications/{id}/messages
     */
    #[Route('/applications/{id}/messages', methods: ['GET'])]
    public function listMessages(
        int $id, // ID Заявки
        #[CurrentUser] User $user // Текущий пользователь из JWT
    ): JsonResponse {

        // Вся логика (проверка прав, фильтрация 'pending'/'approved')
        // спрятана внутри ChatService.
        // Он "выбросит" 403 Exception, если у $user нет доступа.
        $messages = $this->chatService->getMessagesForApplication($id, $user);

        // Используем 'chat:read', чтобы вернуть чистый JSON
        return $this->json($messages, 200, [], ['groups' => 'chat:read']);
    }

    /**
     * Эндпоинт "Отправить сообщение в чат"
     *
     * POST /api/messages
     */
    #[Route('/messages', methods: ['POST'])]
    public function sendMessage(
        #[MapRequestPayload] SendMessageDTO $dto, // <-- Авто-валидация DTO
        #[CurrentUser] User $sender // <-- Текущий пользователь из JWT
    ): JsonResponse {

        // Вся логика (проверка прав, 'pending'/'approved',
        // привязка документа, "крик" в очередь 'NewMessageEvent')
        // спрятана внутри ChatService.
        $message = $this->chatService->sendMessage($dto, $sender);

        // 201 Created
        return $this->json($message, 201, [], ['groups' => 'chat:read']);
    }
}
