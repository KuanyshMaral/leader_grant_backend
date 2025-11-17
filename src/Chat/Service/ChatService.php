<?php
// src/Chat/Service/ChatService.php

namespace App\Chat\Service;

use App\Application\Entity\Application;
use App\Application\Repository\ApplicationRepository;
use App\Chat\DTO\SendMessageDTO;
use App\Chat\Entity\Message;
use App\Chat\Exception\ChatAccessDeniedException;
use App\Chat\Repository\MessageRepository;
use App\Document\Repository\DocumentRepository;
use App\User\Entity\User;
use App\Chat\Event\NewMessageEvent;
use App\Chat\Event\MessageApprovedEvent; // <-- ДОБАВЛЕНО
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Messenger\MessageBusInterface; // <-- ДОБАВЛЕНО

class ChatService
{
    public function __construct(
        private readonly MessageRepository $messageRepository,
        private readonly ApplicationRepository $applicationRepository,
        private readonly DocumentRepository $documentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $bus // <-- ИНЪЕКЦИЯ $bus ДОБАВЛЕНА
    ) {
    }

    /**
     * Создает новое сообщение в чате, применяя правила модерации.
     *
     * @throws ChatAccessDeniedException
     */
    public function sendMessage(SendMessageDTO $dto, User $sender): Message
    {
        $application = $this->applicationRepository->find($dto->application_id);

        // 1. Проверяем, имеет ли пользователь доступ к этому чату
        $this->checkChatAccess($sender, $application);

        // 2. Создаем сообщение
        $message = new Message();
        $message->setApplication($application);
        $message->setSenderUser($sender);
        $message->setBody($dto->body);
        $message->setReadStatus(false);

        // 3. КЛЮЧЕВАЯ ЛОГИКА: Модерация
        $role = $sender->getRole();
        $isPending = false; // Флаг, чтобы знать, нужно ли уведомлять админа

        if ($role === 'client' || $role === 'agent') {
            $message->setModerationStatus('pending');
            $isPending = true; // <-- 2. Взводим флаг
        } else {
            $message->setModerationStatus('approved');
        }

        $this->messageRepository->save($message); // Сохраняем, чтобы получить ID

        // 4. Привязываем вложение (документ), если оно есть
        if ($dto->document_id) {
            $document = $this->documentRepository->find($dto->document_id);
            if ($document && $document->getUploaderUser()->getId() === $sender->getId()) {
                $document->setMessage($message);
                $this->documentRepository->save($document);
            }
        }

        // 5. Завершаем транзакцию
        $this->entityManager->flush();

        $this->logger->info('Новое сообщение в чате', [
            'app_id' => $application->getId(),
            'sender_id' => $sender->getId(),
            'status' => $message->getModerationStatus(),
        ]);

        // 6. РЕАЛИЗОВАНО: "Кричим" в очередь, если нужно
        if ($isPending) {
            $this->bus->dispatch(new NewMessageEvent($message->getId()));
        }

        return $message;
    }

    /**
     * Получает отфильтрованную историю сообщений для пользователя.
     *
     * @throws ChatAccessDeniedException
     */
    public function getMessagesForApplication(int $applicationId, User $user): array
    {
        $application = $this->applicationRepository->find($applicationId);
        $this->checkChatAccess($user, $application);

        $qb = $this->messageRepository->createQueryBuilder('m')
            ->where('m.application = :application')
            ->setParameter('application', $application);

        // 2. КЛЮЧЕВАЯ ЛОГИКА: Фильтрация по роли
        $role = $user->getRole();

        if ($role === 'partner') {
            // Партнер (Банк) видит ТОЛЬКО одобренные сообщения
            $qb->andWhere('m.moderation_status = :status')
                ->setParameter('status', 'approved');

        } elseif ($role === 'client' || $role === 'agent') {
            // Клиент/Агент видит ОДОБРЕННЫЕ + СВОИ СОБСТВЕННЫЕ (любой статус)
            $qb->andWhere('m.moderation_status = :status OR m.sender_user = :user')
                ->setParameter('status', 'approved')
                ->setParameter('user', $user);
        }
        // Админ (else) не имеет доп. фильтров - он видит ВСЕ.

        $messages = $qb->orderBy('m.created_at', 'ASC')->getQuery()->getResult();

        // (Здесь в будущем будет логика простановки "read_status = true")

        return $messages;
    }

    /**
     * [ADMIN] Модерирует сообщение.
     */
    public function moderateMessage(int $messageId, string $newStatus, User $adminUser): Message
    {
        if ($adminUser->getRole() !== 'admin') {
            throw new AccessDeniedException('Только администраторы могут модерировать сообщения.');
        }
        if (!in_array($newStatus, ['approved', 'rejected'])) {
            throw new \InvalidArgumentException('Недопустимый статус модерации');
        }
        $message = $this->messageRepository->find($messageId);
        if (!$message) {
            // TODO: Заменить на кастомный MessageNotFoundException
            throw new \Exception('Сообщение не найдено');
        }

        $oldStatus = $message->getModerationStatus();
        $message->setModerationStatus($newStatus);
        $this->messageRepository->save($message, true); // (flush: true)

        $this->logger->info('Сообщение отмодерировано', [/*...*/]);

        // --- [РЕАЛИЗАЦИЯ ПРОБЕЛА ДЛЯ АДМИНА] ---
        // "Кричим" в очередь, что сообщение одобрено (чтобы уведомить Партнера)
        if ($newStatus === 'approved' && $oldStatus === 'pending') {
            $this->bus->dispatch(new MessageApprovedEvent($message->getId()));
        }
        // --- КОНЕЦ ---

        return $message;
    }

    /**
     * Вспомогательный метод для проверки прав доступа к чату.
     */
    private function checkChatAccess(User $user, ?Application $application): void
    {
        if (!$application) {
            throw new ChatAccessDeniedException('Заявка не найдена.');
        }

        $role = $user->getRole();

        if ($role === 'admin') {
            return; // Админ видит всё
        }

        if ($role === 'client' && $application->getClientUser()->getId() === $user->getId()) {
            return; // Это заявка клиента
        }

        if ($role === 'agent' && $application->getAgentUser()?->getId() === $user->getId()) {
            return; // Это заявка агента
        }

        // --- "ЗАГЛУШКА" УБРАНА ---
        // Проверяем, что User-Партнер привязан к тому же Банку,
        // которому принадлежит эта Заявка.
        if ($role === 'partner' && $user->getBank() === $application->getBank()) {
            return;
        }
        // --- КОНЕЦ ---

        // Если ни одно правило не сработало
        throw new ChatAccessDeniedException('Доступ к этому чату запрещен.');
    }
}
