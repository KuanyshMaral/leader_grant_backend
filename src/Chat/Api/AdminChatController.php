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
        // Используем метод с EAGER LOADING чтобы получить application и sender_user
        $messages = $this->messageRepository->findPendingWithRelations();

        // Вручную форматируем ответ для фронтенда
        $result = [];
        foreach ($messages as $msg) {
            $result[] = [
                'id' => $msg->getId(),
                'body' => $msg->getBody(),
                'sender_user' => [
                    'id' => $msg->getSenderUser()->getId(),
                    'fio' => $msg->getSenderUser()->getFio(),
                    'role' => $msg->getSenderUser()->getRole(),
                ],
                'application' => [
                    'id' => $msg->getApplication()->getId(),
                ],
                'moderation_status' => $msg->getModerationStatus()->value,
                'read_status' => $msg->isReadStatus(),
                'created_at' => $msg->getCreatedAt()->format('c'),
            ];
        }

        return $this->json($result);
    }

    /**
     * [Админ] Получает сообщения конкретной заявки для модерации.
     */
    #[Route('/applications/{applicationId}/messages', methods: ['GET'])]
    public function getApplicationMessages(int $applicationId): JsonResponse
    {
        // Получаем все pending сообщения с relations
        $messages = $this->messageRepository->findPendingWithRelations();
        
        // Фильтруем по application ID
        $result = [];
        foreach ($messages as $msg) {
            if ($msg->getApplication()->getId() === $applicationId) {
                $result[] = [
                    'id' => $msg->getId(),
                    'body' => $msg->getBody(),
                    'sender_user' => [
                        'id' => $msg->getSenderUser()->getId(),
                        'fio' => $msg->getSenderUser()->getFio(),
                        'role' => $msg->getSenderUser()->getRole(),
                    ],
                    'application' => [
                        'id' => $msg->getApplication()->getId(),
                    ],
                    'moderation_status' => $msg->getModerationStatus()->value,
                    'read_status' => $msg->isReadStatus(),
                    'created_at' => $msg->getCreatedAt()->format('c'),
                ];
            }
        }
        
        return $this->json($result);
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
    /**
     * [Админ] Получает список заявок, где есть сообщения на модерации.
     */
    #[Route('/applications', methods: ['GET'])]
    public function getModerationApplications(): JsonResponse
    {
        $messages = $this->messageRepository->findPendingWithRelations();

        $apps = [];
        foreach ($messages as $msg) {
            $app = $msg->getApplication();
            $appId = $app->getId();

            if (!isset($apps[$appId])) {
                $apps[$appId] = [
                    'id' => $appId,
                    'client_name' => $app->getClientUser()->getFio(), // Assumes ClientUser exists
                    'product_name' => $app->getProductType(),
                    'pending_count' => 0,
                    'last_pending_at' => $msg->getCreatedAt()->format('c'),
                ];
            }

            $apps[$appId]['pending_count']++;
            // Update last pending time if this message is newer (though query is ASC, so last one is newest? No, query is ASC, so last iteration is newest)
            $apps[$appId]['last_pending_at'] = $msg->getCreatedAt()->format('c');
        }

        return $this->json(array_values($apps));
    }
}
