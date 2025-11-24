<?php
// src/Agent/Repository/AgentClientInteractionRepository.php

namespace App\Agent\Repository;

use App\Agent\Entity\AgentClientInteraction;
use App\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ОПТИМИЗИРОВАНО: Добавлены методы с EAGER LOADING для взаимодействий
 */
class AgentClientInteractionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AgentClientInteraction::class);
    }

    public function save(AgentClientInteraction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Получить все взаимодействия агента с клиентом с EAGER LOADING.
     */
    public function findByAgentAndClient(User $agent, User $client): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.agent', 'a')->addSelect('a')
            ->leftJoin('i.client', 'c')->addSelect('c')
            ->where('i.agent = :agent')
            ->andWhere('i.client = :client')
            ->setParameter('agent', $agent)
            ->setParameter('client', $client)
            ->orderBy('i.interactionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * [НОВЫЙ] Получить последние взаимодействия агента.
     */
    public function findLatestByAgent(User $agent, int $limit = 10): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.client', 'c')->addSelect('c')
            ->where('i.agent = :agent')
            ->setParameter('agent', $agent)
            ->orderBy('i.interactionDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * [НОВЫЙ] Получить статистику взаимодействий агента.
     */
    public function getAgentInteractionStats(User $agent): array
    {
        $qb = $this->createQueryBuilder('i')
            ->select('
                COUNT(i.id) as total_count,
                COUNT(DISTINCT i.client) as unique_clients,
                i.type,
                COUNT(i.id) as type_count
            ')
            ->where('i.agent = :agent')
            ->setParameter('agent', $agent)
            ->groupBy('i.type');

        return $qb->getQuery()->getResult();
    }
}
