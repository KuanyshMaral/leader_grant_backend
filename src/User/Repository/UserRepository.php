<?php
// src/User/Repository/UserRepository.php

namespace App\User\Repository;

use App\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // --- Кастомные методы, которые нам понадобятся ---

    /**
     * [РЕАЛИЗОВАНО] Находит пользователей по конкретной роли.
     * (Нужно для Админки: "Показать всех Агентов").
     *
     * @return User[]
     */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u') // 'u' - это псевдоним для User
        ->andWhere('u.role = :role')
            ->setParameter('role', $role)
            ->orderBy('u.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * [РЕАЛИЗОВАНО] Находит пользователей, ожидающих проверки админом.
     * (Нужно для Админки: "Очередь на аккредитацию").
     *
     * @return User[]
     */
    public function findPendingAccreditation(): array
    {
        // (Мы договорились, что статус 'pending_review' - это "на проверке у админа")
        return $this->createQueryBuilder('u')
            ->andWhere('u.status = :status')
            ->setParameter('status', 'pending_review')
            ->orderBy('u.created_at', 'ASC') // (Показать сначала старых)
            ->getQuery()
            ->getResult();
    }

    /**
     * [РЕАЛИЗОВАНО] Находит email'ы всех сотрудников (Партнеров)
     * для конкретного банка.
     *
     * @return string[]
     */
    public function findPartnerEmailsByBank(int $bankId): array
    {
        $results = $this->createQueryBuilder('u')
            ->select('u.email') // Выбираем только email
            ->andWhere('u.role = :role')
            ->andWhere('u.bank = :bankId')
            ->setParameter('role', 'partner')
            ->setParameter('bankId', $bankId)
            ->getQuery()
            ->getScalarResult(); // Возвращает [ ['email' => 'a@b.com'], ... ]

        // "Выпрямляем" массив
        return array_column($results, 'email');
    }
}
