<?php
// src/Chat/MessageHandler/NewMessageEventHandler.php

namespace App\Chat\MessageHandler;

use App\Chat\Event\NewMessageEvent;
use App\Chat\Repository\MessageRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class NewMessageEventHandler
{
    // TODO: В будущем, получать список email'ов админов из БД
    private const ADMIN_EMAIL = 'admin@leader-group.ru'; // Заглушка: email админа

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly MessageRepository $messageRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Этот метод будет ВЫЗВАН ВОРКЕРОМ (асинхронно)
     */
    public function __invoke(NewMessageEvent $event)
    {
        // 1. Получаем сообщение из БД
        $message = $this->messageRepository->find($event->getMessageId());
        if (!$message || $message->getModerationStatus() !== 'pending') {
            $this->logger->warning(
                '[NewMessageHandler] Сообщение не найдено или уже было обработано.',
                ['message_id' => $event->getMessageId()]
            );
            return;
        }

        $application = $message->getApplication();
        $sender = $message->getSenderUser();

        $this->logger->info(
            '[NewMessageHandler] Новое сообщение на модерацию. Отправка уведомления админу.',
            ['app_id' => $application->getId(), 'sender_id' => $sender->getId()]
        );

        // 2. Собираем письмо
        $subject = sprintf('Новое сообщение на модерацию (Заявка №%d)', $application->getId());
        $body = sprintf(
            "В чат по заявке №%d поступило новое сообщение от %s.\n\nТекст сообщения: \n\"%s\"\n\nТребуется модерация в админ-панели.",
            $application->getId(),
            $sender->getFio(),
            $message->getBody()
        );

        $email = (new Email())
            ->from('no-reply@leader-group.ru')
            ->to(self::ADMIN_EMAIL)
            ->subject($subject)
            ->text($body);

        // 3. Отправляем
        try {
            $this->mailer->send($email);
            $this->logger->info(
                '[NewMessageHandler] Уведомление админу о модерации успешно отправлено.'
            );
        } catch (\Exception $e) {
            $this->logger->error(
                '[NewMessageHandler] Сбой при отправке email админу.',
                ['error' => $e->getMessage()]
            );
        }
    }
}
