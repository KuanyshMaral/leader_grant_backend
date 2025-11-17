<?php
// src/User/MessageHandler/AccreditationSubmittedEventHandler.php
namespace App\User\MessageHandler;

use App\User\Event\AccreditationSubmittedEvent;
use App\User\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

// Эта строка "говорит" Symfony (Воркеру), что этот класс
// должен "слушать" событие AccreditationSubmittedEvent
#[AsMessageHandler]
class AccreditationSubmittedEventHandler
{
    // TODO: В будущем, получать список email'ов админов из БД
    private const ADMIN_EMAIL = 'admin@leader-group.ru'; // Заглушка: email админа

    public function __construct(
        private readonly MailerInterface $mailer, // "Почтальон" Symfony
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Этот метод будет ВЫЗВАН ВОРКЕРОМ (асинхронно)
     * когда в очередь попадет AccreditationSubmittedEvent.
     */
    public function __invoke(AccreditationSubmittedEvent $event)
    {
        // 1. Получаем пользователя из БД
        $user = $this->userRepository->find($event->getUserId());

        if (!$user) {
            $this->logger->error(
                '[AccreditationSubmittedEventHandler] Пользователь не найден, не могу отправить уведомление.',
                ['user_id' => $event->getUserId()]
            );
            return;
        }

        $company = $user->getCompany();
        if (!$company) {
            $this->logger->error(
                '[AccreditationSubmittedEventHandler] У пользователя нет компании, не могу отправить уведомление.',
                ['user_id' => $event->getUserId()]
            );
            return;
        }

        $this->logger->info(
            '[AccreditationSubmittedEventHandler] Отправка уведомления админу о новой аккредитации.',
            ['user_id' => $user->getId(), 'admin_email' => self::ADMIN_EMAIL]
        );

        // 2. Собираем письмо
        $subject = sprintf('Новая заявка на аккредитацию: %s', $company->getName());
        $body = sprintf(
            "Пользователь %s (ID: %d, Email: %s) подал заявку на аккредитацию.\n\nКомпания: %s\nИНН: %s\n\nТребуется проверка в админ-панели.",
            $user->getFio(),
            $user->getId(),
            $user->getEmail(),
            $company->getName(),
            $company->getInn()
        );

        $email = (new Email())
            ->from('no-reply@leader-group.ru') // Адрес, который вы подтвердили в SendGrid
            ->to(self::ADMIN_EMAIL)
            ->subject($subject)
            ->text($body);

        // 3. Отправляем
        try {
            $this->mailer->send($email);

            $this->logger->info(
                '[AccreditationSubmittedEventHandler] Уведомление админу успешно отправлено.'
            );
        } catch (\Exception $e) {
            $this->logger->error(
                '[AccreditationSubmittedEventHandler] Сбой при отправке email админу.',
                ['error' => $e->getMessage()]
            );
        }
    }
}
