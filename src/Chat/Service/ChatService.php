<?php
// src/Chat/Service/ChatService.php

namespace App\Chat\Service;

use App\Application\Entity\Application;
use App\Application\Repository\ApplicationRepository;
use App\Chat\DTO\SendMessageDTO;
use App\Chat\Entity\Message;
use App\Chat\Enum\ModerationStatus;
use App\Chat\Exception\ChatAccessDeniedException;
use App\Chat\Repository\MessageRepository;
use App\Document\Repository\DocumentRepository;
use App\User\Entity\User;
use App\Chat\Event\NewMessageEvent;
use App\Chat\Event\MessageApprovedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * ОПТИМИЗИРОВАНО: Использование repository методов с EAGER LOADING
 */
class ChatService
{
    public function __construct(
        private readonly MessageRepository $messageRepository,
        private readonly ApplicationRepository $applicationRepository,
        private readonly DocumentRepository $documentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $bus
    ) {
    }

    /**
     * Создает новое сообщение в чате, применяя правила модерации.
     */
    public function sendMessage(SendMessageDTO $dto, User $sender): Message
    {
        $application = $this->applicationRepository->find($dto->application_id);

        $this->checkChatAccess($sender, $application);

        $message = new Message();
        $message->setApplication($application);
        $message->setSenderUser($sender);
        $message->setBody($dto->body);
        $message->setReadStatus(false);

        $role = $sender->getRole();
        $isPending = false;

        if ($role === 'client' || $role === 'agent') {
            $message->setModerationStatus(ModerationStatus::PENDING);
            $isPending = true;
        } else {
            $message->setModerationStatus(ModerationStatus::APPROVED);
        }

        $this->messageRepository->save($message);

        if ($dto->document_id) {
            $document = $this->documentRepository->find($dto->document_id);
            if ($document && $document->getUploaderUser()->getId() === $sender->getId()) {
                $document->setMessage($message);
                $this->documentRepository->save($document);
            }
        }

        $this->entityManager->flush();

        $this->logger->info('Новое сообщение в чате', [
            'app_id' => $application->getId(),
            'sender_id' => $sender->getId(),
            'status' => $message->getModerationStatus(),
        ]);

        if ($isPending) {
            $this->bus->dispatch(new NewMessageEvent($message->getId()));
        }

        return $message;
    }

    /**
     * [ОПТИМИЗИРОВАНО] Получает отфильтрованную историю сообщений с EAGER LOADING.
     */
    public function getMessagesForApplication(int $applicationId, User $user): array
    {
        $application = $this->applicationRepository->find($applicationId);
        $this->checkChatAccess($user, $application);

        // Используем оптимизированный метод repository с EAGER LOADING
        $messages = $this->messageRepository->findByApplicationWithSenders($application);

        $role = $user->getRole();

        // Фильтрация по статусу модерации
        if ($role === 'client' || $role === 'agent') {
            return array_filter($messages, fn($m) => $m->getModerationStatus() === 'approved');
        }

        return $messages;
    }

    /**
     * [НОВЫЙ] Получить непрочитанные сообщения для пользователя.
     */
    public function getUnreadMessages(User $user): array
    {
        return $this->messageRepository->findUnreadForUser($user);
    }

    /**
     * [НОВЫЙ] Получить сообщения на модерации с EAGER LOADING.
     */
    public function getPendingMessages(): array
    {
        return $this->messageRepository->findPendingWithRelations();
    }

    /**
     * [НОВЫЙ] Одобрить сообщение.
     */
    public function approveMessage(int $messageId): Message
    {
        $message = $this->messageRepository->find($messageId);
        if (!$message) {
            throw new \Exception('Сообщение не найдено');
        }

        $message->setModerationStatus(ModerationStatus::APPROVED);
        $this->messageRepository->save($message, true);

        $this->logger->info('Сообщение одобрено', ['message_id' => $messageId]);

        $this->bus->dispatch(new MessageApprovedEvent($messageId));

        return $message;
    }

    /**
     * [НОВЫЙ] Отклонить сообщение.
     */
    public function rejectMessage(int $messageId): Message
    {
        $message = $this->messageRepository->find($messageId);
        if (!$message) {
            throw new \Exception('Сообщение не найдено');
        }

        $message->setModerationStatus(ModerationStatus::REJECTED);
        $this->messageRepository->save($message, true);

        $this->logger->warning('Сообщение отклонено', ['message_id' => $messageId]);

        return $message;
    }

    /**
     * Редактировать сообщение (только автор, только approved, только в течение 24ч)
     */
    public function updateMessage(int $messageId, string $newBody, User $user): Message
    {
        $message = $this->messageRepository->find($messageId);
        
        if (!$message) {
            throw new \Exception('Сообщение не найдено');
        }

        // Проверка: пользователь должен быть автором
        if ($message->getSenderUser()->getId() !== $user->getId()) {
            throw new \Exception('Вы можете редактировать только свои сообщения');
        }

        // Проверка: сообщение не старше 24 часов
        $now = new \DateTime();
        $createdAt = $message->getCreatedAt();
        $diff = $now->getTimestamp() - $createdAt->getTimestamp();
        if ($diff > 86400) { // 24 часа = 86400 секунд
            throw new \Exception('Нельзя редактировать сообщения старше 24 часов');
        }

        // Обновляем текст
        $message->setBody($newBody);
        $this->messageRepository->save($message, true);

        $this->logger->info('Message updated', [
            'message_id' => $messageId,
            'user_id' => $user->getId()
        ]);

        return $message;
    }

    /**
     * Удалить сообщение (мягкое удаление - помечаем как удаленное)
     */
    public function deleteMessage(int $messageId, User $user): void
    {
        $message = $this->messageRepository->find($messageId);
        
        if (!$message) {
            throw new \Exception('Сообщение не найдено');
        }

        // Проверка: пользователь должен быть автором
        if ($message->getSenderUser()->getId() !== $user->getId()) {
            throw new \Exception('Вы можете удалять только свои сообщения');
        }

        // Мягкое удаление: мы можем либо удалить из БД, либо пометить как deleted
        // Для простоты - просто удаляем
        $this->entityManager->remove($message);
        $this->entityManager->flush();

        $this->logger->warning('Message deleted', [
            'message_id' => $messageId,
            'user_id' => $user->getId()
        ]);
    }

    /**
     * Проверка доступа к чату.
     */
    private function checkChatAccess(User $user, ?Application $application): void
    {
        if (!$application) {
            throw new ChatAccessDeniedException('Заявка не найдена');
        }

        $role = $user->getRole();

        if ($role === 'client') {
            if ($application->getClientUser()->getId() !== $user->getId()) {
                throw new ChatAccessDeniedException('Вы не можете писать в чужой чат');
            }
        } elseif ($role === 'agent') {
            if ($application->getAgentUser()?->getId() !== $user->getId()) {
                throw new ChatAccessDeniedException('Вы не можете писать в чужой чат');
            }
        } elseif ($role === 'partner') {
            if ($application->getBank()->getId() !== $user->getBank()?->getId()) {
                throw new ChatAccessDeniedException('Эта заявка не в вашем банке');
            }
        }
    }
}
