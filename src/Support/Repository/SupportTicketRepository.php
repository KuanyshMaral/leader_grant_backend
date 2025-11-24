<?php
// src/Support/Repository/SupportTicketRepository.php

namespace App\Support\Repository;

use App\Support\Entity\SupportTicket;
use App\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SupportTicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportTicket::class);
    }

    public function save(SupportTicket $ticket): void
    {
        $this->getEntityManager()->persist($ticket);
        $this->getEntityManager()->flush();
    }

    /**
     * Find all tickets for a specific user
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find ticket by ID and user (for access control)
     */
    public function findOneByIdAndUser(int $id, User $user): ?SupportTicket
    {
        return $this->createQueryBuilder('t')
            ->where('t.id = :id')
            ->andWhere('t.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
