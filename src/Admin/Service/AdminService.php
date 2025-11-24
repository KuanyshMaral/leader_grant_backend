<?php
// src/Admin/Service/AdminService.php

namespace App\Admin\Service;

use App\User\Entity\User;
use App\User\Repository\UserRepository;
use App\User\Enum\UserRole;
use App\User\Enum\UserStatus;
use App\Chat\Entity\Message;
use App\Chat\Enum\ModerationStatus;
use App\Chat\Repository\MessageRepository;
use App\Bank\Repository\BankRepository;
use App\Document\Repository\DocumentRepository;
use App\Application\Repository\ApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly MessageRepository $messageRepository,
        private readonly BankRepository $bankRepository,
        private readonly DocumentRepository $documentRepository,
        private readonly ApplicationRepository $applicationRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly LoggerInterface $logger
    ) {
    }

    // ============ CHAT MODERATION ============

    /**
     * Получить список сообщений на модерации (pending).
     * 
     * @return Message[]
     */
    public function getPendingMessages(): array
    {
        return $this->messageRepository->findBy(
            ['moderation_status' => 'pending'],
            ['created_at' => 'DESC']
        );
    }

    /**
     * Одобрить сообщение.
     */
    public function approveMessage(int $messageId, User $admin): void
    {
        $message = $this->messageRepository->find($messageId);

        if (!$message) {
            throw new \Exception('Сообщение не найдено');
        }

        $message->setModerationStatus(ModerationStatus::APPROVED);
        $this->entityManager->flush();

        $this->logger->info('Message approved by admin', [
            'message_id' => $messageId,
            'admin_id' => $admin->getId()
        ]);
    }

    /**
     * Отклонить сообщение.
     */
    public function rejectMessage(int $messageId, User $admin): void
    {
        $message = $this->messageRepository->find($messageId);

        if (!$message) {
            throw new \Exception('Сообщение не найдено');
        }

        $message->setModerationStatus(ModerationStatus::REJECTED);
        $this->entityManager->flush();

        $this->logger->info('Message rejected by admin', [
            'message_id' => $messageId,
            'admin_id' => $admin->getId()
        ]);
    }

    /**
     * Получить список заявок с pending сообщениями.
     * 
     * @return array Массив с данными заявок и количеством pending сообщений
     */
    public function getApplicationsWithPendingMessages(): array
    {
        // Получаем все pending сообщения с заявками
        $qb = $this->messageRepository->createQueryBuilder('m')
            ->select('
                a.id as application_id,
                COUNT(m.id) as pending_count,
                MAX(m.created_at) as last_pending_at
            ')
            ->join('m.application', 'a')
            ->where('m.moderation_status = :pending')
            ->setParameter('pending', 'pending')
            ->groupBy('a.id')
            ->orderBy('last_pending_at', 'DESC');

        $results = $qb->getQuery()->getResult();

        // Загружаем полные данные заявок
        $applications = [];
        foreach ($results as $result) {
            $application = $this->applicationRepository->find($result['application_id']);
            if ($application) {
                $applications[] = [
                    'id' => $application->getId(),
                    'product_name' => $application->getProductName(),
                    'client_name' => $application->getUser()->getCompany()?->getName() ?? $application->getUser()->getFio(),
                    'pending_count' => $result['pending_count'],
                    'last_pending_at' => $result['last_pending_at']->format('Y-m-d H:i:s'),
                ];
            }
        }

        return $applications;
    }

    // ============ USER ACCREDITATION ============

    /**
     * Получить список пользователей на аккредитации (pending).
     * Исключаем админов - они не нуждаются в аккредитации.
     *
     * @return User[]
     */
    public function getPendingAccreditations(): array
    {
        return $this->userRepository->createQueryBuilder('u')
            ->where('u.accreditationStatus = :status')
            ->andWhere('u.role != :adminRole')
            ->setParameter('status', 'pending')
            ->setParameter('adminRole', 'admin')
            ->orderBy('u.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Одобрить аккредитацию пользователя.
     */
    public function approveAccreditation(int $userId, User $admin): void
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw new \Exception('Пользователь не найден');
        }

        $user->setAccreditationStatus('approved');
        $user->setAccreditationDate(new \DateTimeImmutable());
        $user->setStatus('active'); // Активируем пользователя
        $this->entityManager->flush();

        $this->logger->info('User accreditation approved', [
            'user_id' => $userId,
            'admin_id' => $admin->getId()
        ]);

        // TODO: Отправить email пользователю об одобрении
    }

    /**
     * Отклонить аккредитацию пользователя.
     */
    public function rejectAccreditation(int $userId, User $admin, string $reason): void
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw new \Exception('Пользователь не найден');
        }

        $user->setAccreditationStatus('rejected');
        $user->setRejectionReason($reason);
        $user->setAccreditationDate(new \DateTimeImmutable());
        $this->entityManager->flush();

        $this->logger->info('User accreditation rejected', [
            'user_id' => $userId,
            'reason' => $reason,
            'admin_id' => $admin->getId()
        ]);

        // TODO: Отправить email пользователю с причиной отказа
    }

    /**
     * Получить документы пользователя.
     * Ищет как документы загруженные пользователем, так и документы его компании.
     * 
     * @param int $userId
     * @return array
     */
    public function getUserDocuments(int $userId): array
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw new \Exception('Пользователь не найден');
        }

        // Use findAllByClient - it searches both by uploader_user and by company
        return $this->documentRepository->findAllByClient($user);
    }

    // ============ PARTNER CREATION ============

    /**
     * Получить список всех банков.
     * 
     * @return array
     */
    public function getAllBanks(): array
    {
        return $this->bankRepository->findAll();
    }

    /**
     * Создать партнёра (сотрудника банка).
     */
    public function createPartner(array $data, User $admin): User
    {
        // Валидация
        if (empty($data['bank_id']) || empty($data['email']) || empty($data['password']) || empty($data['fio'])) {
            throw new \InvalidArgumentException('Недостаточно данных для создания партнёра');
        }

        // Проверка существования банка
        $bank = $this->bankRepository->find($data['bank_id']);
        if (!$bank) {
            throw new \Exception('Банк не найден');
        }

        // Проверка существования email
        $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            throw new \Exception('Пользователь с таким email уже существует');
        }

        // Создание пользователя
        $user = new User();
        $user->setEmail($data['email']);
        $user->setFio($data['fio']);
        $user->setPhone($data['phone'] ?? '');
        $user->setRole(UserRole::PARTNER); // роль партнёра (сотрудника банка)
        $user->setStatus(UserStatus::ACTIVE);
        $user->setBank($bank);
        $user->setAccreditationStatus('approved'); // Партнёры одобрены сразу

        // Хешируем пароль
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPasswordHash($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->logger->info('Partner created by admin', [
            'user_id' => $user->getId(),
            'bank_id' => $bank->getId(),
            'admin_id' => $admin->getId()
        ]);

        // TODO: Отправить email партнёру с данными для входа

        return $user;
    }
}
