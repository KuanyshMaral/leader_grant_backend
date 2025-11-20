<?php
// src/User/Repository/ClientAgentLinkRepository.php

namespace App\User\Repository;

use App\User\Entity\ClientAgentLink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClientAgentLink>
 */
class ClientAgentLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientAgentLink::class);
    }

    public function save(ClientAgentLink $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ClientAgentLink $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Находит всех клиентов, привязанных к агенту.
     * @return ClientAgentLink[]
     */
    public function findClientsByAgent(int $agentId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.agent_user = :agentId')
            ->setParameter('agentId', $agentId)
            ->andWhere('c.status = :status')
            ->setParameter('status', 'linked') // Берем только активные связи
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
