<?php
// src/User/MessageHandler/UserRegisteredEventHandler.php
namespace App\User\MessageHandler;

use App\User\Event\UserRegisteredEvent;
use App\User\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

// Говорим Symfony, что этот класс "слушает" UserRegisteredEvent
#[AsMessageHandler]
class UserRegisteredEventHandler
{
    public function __construct(
        private readonly MailerInterface $mailer, // "Почтальон"
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $logger
    ) {}

    // Этот метод будет вызван ВОРКЕРОМ
    public function __invoke(UserRegisteredEvent $event)
    {
        $user = $this->userRepository->find($event->getUserId());
        if (!$user) {
            $this->logger->error('Не могу отправить Welcome-email: User не найден', ['user_id' => $event->getUserId()]);
            return;
        }

        $this->logger->info('Воркер: Отправляю Welcome-email...', ['email' => $user->getEmail()]);

        // (Здесь будет красивая логика с Twig-шаблоном)
        $email = (new Email())
            ->from('support@leader-group.ru')
            ->to($user->getEmail())
            ->subject('Добро пожаловать в Leader Group!')
            ->text('Спасибо за регистрацию, ' . $user->getFio());

        try {
            $this->mailer->send($email);
            $this->logger->info('Welcome-email успешно отправлен');
        } catch (\Exception $e) {
            $this->logger->error('Ошибка отправки Welcome-email', ['error' => $e->getMessage()]);
        }
    }
}
