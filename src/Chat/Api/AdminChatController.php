<?php
// src/Chat/Api/AdminChatController.php

namespace App\Chat\Api;

use App\Chat\Repository\MessageRepository;
use App\Chat\Service\ChatService;
use App\User\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/chat')]
#[IsGranted('ROLE_ADMIN')] // <-- ВЕСЬ КОНТРОЛЛЕР ЗАЩИЩЕН
class AdminChatController extends AbstractController
{
    public function __construct(
        private readonly ChatService $chatService,
        private readonly MessageRepository $messageRepository
    ) {
    }

    /**
     * [Админ] Получает "Очередь на модерацию".
     * (Сообщения со статусом 'pending')
     */
    #[Route('/pending', methods: ['GET'])]
    public function getPendingMessages(): JsonResponse
    {
        // 1. Используем новый метод репозитория
        $messages = $this->messageRepository->findPendingModeration();

        // 2. Возвращаем "чистый" JSON (мы настроили #[Groups('chat:read')] в Message.php)
        return $this->json($messages, 200, [], ['groups' => 'chat:read']);
    }

    /**
     * [Админ] Одобряет сообщение.
     */
    #[Route('/messages/{id}/approve', methods: ['POST'])]
    public function approveMessage(int $id, #[CurrentUser] User $admin): JsonResponse
    {
        // 3. Вызываем сервис, который мы уже написали
        // (Он сам "крикнет" асинхронное событие MessageApprovedEvent)
        $message = $this->chatService->moderateMessage($id, 'approved', $admin);

        return $this->json($message, 200, [], ['groups' => 'chat:read']);
    }

    /**
     * [Админ] Отклоняет сообщение.
     */
    #[Route('/messages/{id}/reject', methods: ['POST'])]
    public function rejectMessage(int $id, #[CurrentUser] User $admin): JsonResponse
    {
        // 4. Вызываем тот же сервис
        $message = $this->chatService->moderateMessage($id, 'rejected', $admin);

        return $this->json($message, 200, [], ['groups' => 'chat:read']);
    }
}
