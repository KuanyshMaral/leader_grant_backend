<?php
// src/Chat/Repository/MessageRepository.php

namespace App\Chat\Repository;

use App\Chat\Entity\Message;
use App\Application\Entity\Application;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 * 
 * ОПТИМИЗИРОВАНО: Добавлены методы с EAGER LOADING для чатов
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

    /**
     * Находит все сообщения, ожидающие модерации.
     */
    public function findPendingModeration(): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.moderation_status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('m.created_at', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * [НОВЫЙ] Находит сообщения заявки с EAGER LOADING отправителей.
     * Предотвращает N+1 при загрузке чата.
     */
    public function findByApplicationWithSenders(Application $application): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.sender_user', 's')->addSelect('s')
            ->where('m.application = :application')
            ->setParameter('application', $application)
            ->orderBy('m.created_at', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * [НОВЫЙ] Находит непрочитанные сообщения для пользователя.
     */
    public function findUnreadForUser(\App\User\Entity\User $user): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.application', 'a')
            ->where('m.read_status = :read')
            ->andWhere('m.sender_user != :user')
            ->andWhere('(a.client_user = :user OR a.agent_user = :user)')
            ->setParameter('read', false)
            ->setParameter('user', $user)
            ->orderBy('m.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * [НОВЫЙ] Находит сообщения на модерации с EAGER LOADING.
     */
    public function findPendingWithRelations(): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.sender_user', 's')->addSelect('s')
            ->leftJoin('m.application', 'a')->addSelect('a')
            ->where('m.moderation_status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('m.created_at', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
