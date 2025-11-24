<?php
// src/User/Repository/UserRepository.php

namespace App\User\Repository;

use App\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 * 
 * ОПТИМИЗИРОВАНО: Добавлены методы с EAGER LOADING для предотвращения N+1 запросов
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

    // --- Кастомные методы ---

    /**
     * Находит пользователей по конкретной роли.
     */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.role = :role')
            ->setParameter('role', $role)
            ->orderBy('u.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Находит пользователей, ожидающих проверки админом.
     */
    public function findPendingAccreditation(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.status = :status')
            ->setParameter('status', 'pending_review')
            ->orderBy('u.created_at', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Находит email'ы всех сотрудников (Партнеров) для конкретного банка.
     */
    public function findPartnerEmailsByBank(int $bankId): array
    {
        $results = $this->createQueryBuilder('u')
            ->select('u.email')
            ->andWhere('u.role = :role')
            ->andWhere('u.bank = :bankId')
            ->setParameter('role', 'partner')
            ->setParameter('bankId', $bankId)
            ->getQuery()
            ->getScalarResult();

        return array_column($results, 'email');
    }

    /**
     * [НОВЫЙ] Находит клиентов банка с статистикой заявок (EAGER LOADING).
     * Перенесено из PartnerService для оптимизации.
     */
    public function findBankClientsWithStats(\App\Bank\Entity\Bank $bank): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        $qb->select('
                u.id,
                u.fio,
                u.email,
                COALESCE(c.name, u.fio) as company_name,
                c.inn,
                COUNT(DISTINCT a.id) as applications_count,
                COALESCE(SUM(CASE WHEN a.status = :approved THEN a.amount ELSE 0 END), 0) as total_approved_sum
            ')
            ->from('App\User\Entity\User', 'u')
            ->leftJoin('u.company', 'c')
            ->leftJoin('App\Application\Entity\Application', 'a', 'WITH', 'a.client_user = u')
            ->where('a.bank = :bank')
            ->andWhere('u.role = :client_role')
            ->groupBy('u.id, c.name, c.inn, u.fio, u.email')
            ->having('COUNT(DISTINCT a.id) > 0')
            ->setParameter('bank', $bank)
            ->setParameter('client_role', 'client')
            ->setParameter('approved', 'approved');

        return $qb->getQuery()->getResult();
    }

    /**
     * [НОВЫЙ] Находит агентов банка с статистикой (EAGER LOADING).
     * Перенесено из PartnerService.
     */
    public function findBankAgentsWithStats(\App\Bank\Entity\Bank $bank): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        $qb->select('
                agent.id,
                agent.fio,
                agent.email,
                COUNT(DISTINCT a.id) as deals_count,
                COALESCE(SUM(CASE WHEN a.status = :approved THEN a.amount ELSE 0 END), 0) as total_volume
            ')
            ->from('App\User\Entity\User', 'agent')
            ->join('App\User\Entity\User', 'client', 'WITH', 'client.referrer_agent = agent')
            ->join('App\Application\Entity\Application', 'a', 'WITH', 'a.client_user = client')
            ->where('a.bank = :bank')
            ->andWhere('agent.role = :agent_role')
            ->groupBy('agent.id, agent.fio, agent.email')
            ->having('COUNT(DISTINCT a.id) > 0')
            ->setParameter('bank', $bank)
            ->setParameter('agent_role', 'agent')
            ->setParameter('approved', 'approved');

        return $qb->getQuery()->getResult();
    }

    /**
     * [НОВЫЙ] Находит пользователя с EAGER LOADING всех связей.
     * Предотвращает N+1 при загрузке профиля.
     */
    public function findOneWithRelations(int $id): ?User
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.company', 'c')->addSelect('c')
            ->leftJoin('u.bank', 'b')->addSelect('b')
            ->leftJoin('u.personal_manager', 'pm')->addSelect('pm')
            ->leftJoin('u.referrer_agent', 'ra')->addSelect('ra')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * [НОВЫЙ] Находит всех менеджеров (админов) для назначения клиентам.
     */
    public function findAllManagers(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.role = :role')
            ->andWhere('u.status = :status')
            ->setParameter('role', 'admin')
            ->setParameter('status', 'active')
            ->orderBy('u.fio', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * [НОВЫЙ] Находит клиентов агента с EAGER LOADING компаний.
     */
    public function findAgentClientsWithCompanies(User $agent): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.company', 'c')->addSelect('c')
            ->where('u.referrer_agent = :agent')
            ->andWhere('u.role = :role')
            ->setParameter('agent', $agent)
            ->setParameter('role', 'client')
            ->orderBy('u.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
