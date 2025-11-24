<?php
// src/Bank/Repository/BankRepository.php

namespace App\Bank\Repository;

use App\Bank\Entity\Bank;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bank>
 * 
 * ОПТИМИЗИРОВАНО: Добавлены методы для фильтрации и поиска банков
 */
class BankRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bank::class);
    }

    public function save(Bank $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Bank $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * [НОВЫЙ] Находит банки по статусу аккредитации.
     */
    public function findByAccreditationStatus(string $status): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.accreditationStatus = :status')
            ->setParameter('status', $status)
            ->orderBy('b.accreditationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * [НОВЫЙ] Находит активные банки (для списка в калькуляторе).
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.status = :status')
            ->andWhere('b.accreditationStatus = :accStatus')
            ->setParameter('status', 'active')
            ->setParameter('accStatus', 'approved')
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * [НОВЫЙ] Находит банки, ожидающие аккредитации.
     */
    public function findPendingAccreditation(): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.accreditationStatus = :status')
            ->setParameter('status', 'pending')
            ->orderBy('b.created_at', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
