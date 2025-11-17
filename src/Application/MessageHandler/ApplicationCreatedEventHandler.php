<?php
// src/Application/MessageHandler/ApplicationCreatedEventHandler.php

namespace App\Application\MessageHandler;

use App\Application\Event\ApplicationCreatedEvent;
use App\Application\Repository\ApplicationRepository;
use App\User\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class ApplicationCreatedEventHandler
{
    // TODO: Получать список email'ов админов из БД
    private const ADMIN_EMAIL = 'admin@leader-group.ru'; // Заглушка: email админа

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly ApplicationRepository $applicationRepository,
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Этот метод будет ВЫЗВАН ВОРККЕРОМ (асинхронно)
     */
    public function __invoke(ApplicationCreatedEvent $event)
    {
        // 1. Получаем Клиента
        $client = $this->userRepository->find($event->getClientId());
        if (!$client) {
            $this->logger->error(
                '[AppCreatedHandler] Клиент не найден, не могу отправить уведомление.',
                ['client_id' => $event->getClientId()]
            );
            return;
        }

        // 2. Собираем список получателей
        $recipients = [self::ADMIN_EMAIL];
        if ($client->getPersonalManager()) {
            // Если у клиента есть персональный менеджер, его тоже уведомляем
            $recipients[] = $client->getPersonalManager()->getEmail();
        }
        $recipients = array_unique($recipients); // Убираем дубликаты

        $appIds = implode(', ', $event->getApplicationIds());
        $companyName = $client->getCompany()?->getName() ?? $client->getEmail();

        $this->logger->info(
            '[AppCreatedHandler] Отправка уведомления о новых заявках.',
            ['app_ids' => $appIds, 'recipients' => $recipients]
        );

        // 3. Собираем письмо
        $subject = sprintf('Новые заявки (%d шт) от %s', count($event->getApplicationIds()), $companyName);
        $body = sprintf(
            "В систему поступили новые заявки от клиента %s (ID: %d).\n\nID Заявок: %s\n\nТребуется обработка в админ-панели.",
            $companyName,
            $client->getId(),
            $appIds
        );

        $email = (new Email())
            ->from('no-reply@leader-group.ru') // Адрес, который вы подтвердили в SendGrid
            ->to(...$recipients) // (...$recipients) = 'admin@email.ru', 'manager@email.ru'
            ->subject($subject)
            ->text($body);

        // 4. Отправляем
        try {
            $this->mailer->send($email);

            $this->logger->info(
                '[AppCreatedHandler] Уведомление о заявках успешно отправлено.'
            );
        } catch (\Exception $e) {
            $this->logger->error(
                '[AppCreatedHandler] Сбой при отправке email о заявках.',
                ['error' => $e->getMessage()]
            );
        }
    }
}
