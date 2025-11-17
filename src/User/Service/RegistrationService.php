<?php
// src/User/Service/RegistrationService.php

namespace App\User\Service;

use App\User\DTO\RegisterUserDTO;
use App\User\Entity\User;
use App\User\Exception\UserAlreadyExistsException;
use App\User\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use App\User\Event\UserRegisteredEvent;

class RegistrationService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $bus
    ) {
    }

    /**
     * Создает нового пользователя в системе.
     * @throws UserAlreadyExistsException
     */
    public function register(RegisterUserDTO $dto): User
    {
        // 1. Проверяем, не занят ли email
        $existingUser = $this->userRepository->findOneBy(['email' => $dto->email]);
        if ($existingUser) {
            $this->logger->warning('Попытка регистрации с существующим email', [
                'email' => $dto->email,
            ]);
            throw new UserAlreadyExistsException();
        }

        // 2. Создаем Entity
        $user = new User();
        $user->setEmail($dto->email);
        $user->setFio($dto->fio);
        $user->setPhone($dto->phone);
        $user->setRole($dto->role);

        // 3. Устанавливаем статус (согласно PDF, после регистрации идет аккредитация)
        $user->setStatus('pending_accreditation');

        // 4. Хешируем пароль
        $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->password);
        $user->setPasswordHash($hashedPassword);

        // 5. (Опционально) Привязываем реферала (агента)
        if ($dto->referrer_agent_id) {
            $agent = $this->userRepository->find($dto->referrer_agent_id);
            if ($agent && $agent->getRole() === 'agent') {
                $user->setReferrerAgent($agent);
            }
        }

        // 6. Сохраняем в БД (flush: true, чтобы транзакция завершилась немедленно)
        $this->userRepository->save($user, true);

        $this->logger->info('Новый пользователь зарегистрирован', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
        ]);

        // 4. ЗАМЕНЯЕМ КОММЕНТАРИЙ
        // "Кричим" в шину, что пользователь создан.
        // Messenger сам положит это в очередь 'async' (согласно .yaml)
        $this->bus->dispatch(new UserRegisteredEvent($user->getId()));

        return $user;
    }
}
