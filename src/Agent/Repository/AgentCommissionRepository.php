<?php
// src/Agent/Repository/AgentCommissionRepository.php

namespace App\Agent\Repository;

use App\Agent\Entity\AgentCommission;
use App\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ОПТИМИЗИРОВАНО: Добавлены методы с EAGER LOADING для комиссий
 */
class AgentCommissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AgentCommission::class);
    }

    public function save(AgentCommission $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Получить все комиссии агента с EAGER LOADING заявок и агента.
     */
    public function findByAgent(User $agent): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.agent', 'a')->addSelect('a')
            ->leftJoin('c.application', 'app')->addSelect('app')
            ->where('c.agent = :agent')
            ->setParameter('agent', $agent)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Получить неоплаченные комиссии с EAGER LOADING.
     */
    public function findPending(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.agent', 'a')->addSelect('a')
            ->leftJoin('c.application', 'app')->addSelect('app')
            ->where('c.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * [НОВЫЙ] Получить статистику комиссий агента.
     */
    public function getAgentCommissionStats(User $agent): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('
                COUNT(c.id) as total_count,
                COALESCE(SUM(c.commissionAmount), 0) as total_amount,
                COALESCE(SUM(CASE WHEN c.status = :paid THEN c.commissionAmount ELSE 0 END), 0) as paid_amount,
                COALESCE(SUM(CASE WHEN c.status = :pending THEN c.commissionAmount ELSE 0 END), 0) as pending_amount
            ')
            ->where('c.agent = :agent')
            ->setParameter('agent', $agent)
            ->setParameter('paid', 'paid')
            ->setParameter('pending', 'pending');

        return $qb->getQuery()->getSingleResult();
    }
}
