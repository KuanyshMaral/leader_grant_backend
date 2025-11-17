<?php
// src/Chat/MessageHandler/MessageApprovedEventHandler.php

namespace App\Chat\MessageHandler;

use App\Chat\Event\MessageApprovedEvent;
use App\Chat\Repository\MessageRepository;
use App\User\Entity\User;
use App\User\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class MessageApprovedEventHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly MessageRepository $messageRepository,
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Этот метод будет ВЫЗВАН ВОРКЕРОМ (асинхронно)
     */
    public function __invoke(MessageApprovedEvent $event)
    {
        // 1. Получаем сообщение из БД
        $message = $this->messageRepository->find($event->getMessageId());
        if (!$message) {
            $this->logger->error(
                '[MessageApprovedHandler] Сообщение не найдено, не могу отправить уведомление.',
                ['message_id' => $event->getMessageId()]
            );
            return;
        }

        $application = $message->getApplication();
        $sender = $message->getSenderUser();

        $recipients = []; // Массив email'ов, кому отправить
        $senderRole = $sender->getRole();

        // 2. Определяем, кому отправить уведомление

        if ($senderRole === 'client' || $senderRole === 'agent') {

            // --- "ЗАГЛУШКА" УБРАНА ---
            // Сообщение от Клиента/Агента -> Уведомляем Партнера (Банк)
            // Находим всех сотрудников банка, привязанных к этой заявке
            $bankId = $application->getBank()->getId();
            $partnerEmails = $this->userRepository->findPartnerEmailsByBank($bankId);

            if (empty($partnerEmails)) {
                $this->logger->warning(
                    '[MessageApprovedHandler] Сообщение одобрено, но для банка не найдено ни одного Партнера (сотрудника).',
                    ['app_id' => $application->getId(), 'bank_id' => $bankId]
                );
                return;
            }
            $recipients = $partnerEmails;
            // --- КОНЕЦ ---

        } elseif ($senderRole === 'partner') {

            // Сообщение от Партнера -> Уведомляем Клиента (и Агента, если он есть)
            $client = $application->getClientUser();
            $recipients[] = $client->getEmail();

            if ($application->getAgentUser()) {
                $recipients[] = $application->getAgentUser()->getEmail();
            }
        }
        // (Если отправил Админ, никому не уведомляем, т.к. его сообщение 'approved' по умолчанию)

        if (empty($recipients)) {
            $this->logger->info(
                '[MessageApprovedHandler] Получатели не найдены (например, админ написал админу), отправка отменена.',
                ['app_id' => $application->getId()]
            );
            return;
        }

        // 3. Собираем и отправляем письмо
        $subject = sprintf('Новое сообщение в чате по заявке №%d', $application->getId());
        $body = sprintf(
            "Вам поступило новое сообщение в чате по заявке №%d от %s.\n\nТекст сообщения: \n\"%s\"\n\nПожалуйста, войдите в личный кабинет для ответа.",
            $application->getId(),
            $sender->getFio(),
            $message->getBody()
        );

        $email = (new Email())
            ->from('no-reply@leader-group.ru')
            ->to(...array_unique($recipients))
            ->subject($subject)
            ->text($body);

        try {
            $this->mailer->send($email);
            $this->logger->info('[MessageApprovedHandler] Уведомление о сообщении успешно отправлено.', ['recipients' => $recipients]);
        } catch (\Exception $e) {
            $this->logger->error(
                '[MessageApprovedHandler] Сбой при отправке email о сообщении.',
                ['error' => $e->getMessage()]
            );
        }
    }
}
