<?php
// src/Chat/Repository/MessageRepository.php

namespace App\Chat\Repository;

use App\Chat\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function save(Message $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Message $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // --- [РЕАЛИЗАЦИЯ ДЛЯ АДМИНА] ---

    /**
     * [ADMIN] Находит все сообщения, ожидающие модерации.
     * @return Message[]
     */
    public function findPendingModeration(): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.moderation_status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('m.created_at', 'ASC') // Сначала старые
            ->getQuery()
            ->getResult();
    }
}
