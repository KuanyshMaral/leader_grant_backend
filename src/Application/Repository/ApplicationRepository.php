<?php
// src/Application/Repository/ApplicationRepository.php

namespace App\Application\Repository;

use App\Application\Entity\Application;
use App\User\Entity\User;
use App\Bank\Entity\Bank;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Application>
 * 
 * ОПТИМИЗИРОВАНО: Добавлены методы с EAGER LOADING для заявок
 */
class ApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Application::class);
    }

    public function save(Application $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Application $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * [НОВЫЙ] Найти заявку с EAGER LOADING всех связей.
     * Предотвращает N+1 при загрузке деталей заявки.
     */
    public function findOneWithRelations(int $id): ?Application
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.client_user', 'client')->addSelect('client')
            ->leftJoin('a.agent_user', 'agent')->addSelect('agent')
            ->leftJoin('a.bank', 'bank')->addSelect('bank')
            ->leftJoin('client.company', 'company')->addSelect('company')
            ->where('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * [НОВЫЙ] Найти заявки клиента с EAGER LOADING.
     */
    public function findByClient(User $client): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.agent_user', 'agent')->addSelect('agent')
            ->leftJoin('a.bank', 'bank')->addSelect('bank')
            ->where('a.client_user = :client')
            ->setParameter('client', $client)
            ->orderBy('a.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * [НОВЫЙ] Найти заявки агента с EAGER LOADING.
     */
    public function findByAgent(User $agent): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.client_user', 'client')->addSelect('client')
            ->leftJoin('a.bank', 'bank')->addSelect('bank')
            ->leftJoin('client.company', 'company')->addSelect('company')
            ->where('a.agent_user = :agent')
            ->setParameter('agent', $agent)
            ->orderBy('a.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * [НОВЫЙ] Найти заявки банка с EAGER LOADING.
     */
    public function findByBank(Bank $bank): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.client_user', 'client')->addSelect('client')
            ->leftJoin('a.agent_user', 'agent')->addSelect('agent')
            ->leftJoin('client.company', 'company')->addSelect('company')
            ->where('a.bank = :bank')
            ->setParameter('bank', $bank)
            ->orderBy('a.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * [НОВЫЙ] Найти заявки по статусу с EAGER LOADING.
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.client_user', 'client')->addSelect('client')
            ->leftJoin('a.agent_user', 'agent')->addSelect('agent')
            ->leftJoin('a.bank', 'bank')->addSelect('bank')
            ->where('a.status = :status')
            ->setParameter('status', $status)
            ->orderBy('a.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * [НОВЫЙ] Получить статистику заявок клиента.
     */
    public function getClientApplicationStats(User $client): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('
                COUNT(a.id) as total_count,
                COALESCE(SUM(a.amount), 0) as total_amount,
                COUNT(CASE WHEN a.status = :approved THEN 1 END) as approved_count,
                COUNT(CASE WHEN a.status = :rejected THEN 1 END) as rejected_count,
                COUNT(CASE WHEN a.status = :pending THEN 1 END) as pending_count,
                COALESCE(SUM(CASE WHEN a.status = :approved THEN a.amount ELSE 0 END), 0) as approved_amount
            ')
            ->where('a.client_user = :client')
            ->setParameter('client', $client)
            ->setParameter('approved', 'approved')
            ->setParameter('rejected', 'rejected')
            ->setParameter('pending', 'pending');

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * [НОВЫЙ] Получить статистику заявок агента.
     */
    public function getAgentApplicationStats(User $agent): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('
                COUNT(a.id) as total_count,
                COALESCE(SUM(a.amount), 0) as total_amount,
                COUNT(CASE WHEN a.status = :approved THEN 1 END) as approved_count,
                COUNT(CASE WHEN a.status = :rejected THEN 1 END) as rejected_count,
                COALESCE(SUM(CASE WHEN a.status = :approved THEN a.amount ELSE 0 END), 0) as approved_amount,
                COUNT(DISTINCT a.client_user) as unique_clients
            ')
            ->where('a.agent_user = :agent')
            ->setParameter('agent', $agent)
            ->setParameter('approved', 'approved')
            ->setParameter('rejected', 'rejected');

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * [НОВЫЙ] Получить статистику заявок банка.
     */
    public function getBankApplicationStats(Bank $bank): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('
                COUNT(a.id) as total_count,
                COALESCE(SUM(a.amount), 0) as total_amount,
                COUNT(CASE WHEN a.status = :approved THEN 1 END) as approved_count,
                COUNT(CASE WHEN a.status = :rejected THEN 1 END) as rejected_count,
                COUNT(CASE WHEN a.status = :pending THEN 1 END) as pending_count,
                COALESCE(SUM(CASE WHEN a.status = :approved THEN a.amount ELSE 0 END), 0) as approved_amount,
                COUNT(DISTINCT a.client_user) as unique_clients,
                COUNT(DISTINCT a.agent_user) as unique_agents
            ')
            ->where('a.bank = :bank')
            ->setParameter('bank', $bank)
            ->setParameter('approved', 'approved')
            ->setParameter('rejected', 'rejected')
            ->setParameter('pending', 'pending');

        return $qb->getQuery()->getSingleResult();
    }
}
