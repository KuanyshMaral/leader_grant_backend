<?php
// src/Document/Repository/DocumentRepository.php

namespace App\Document\Repository;

use App\Document\Entity\Document;
use App\User\Entity\User;
use App\Application\Entity\Application;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
 * 
 * ОПТИМИЗИРОВАНО: Добавлены методы с EAGER LOADING для документов
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    public function save(Document $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Document $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * [ОПТИМИЗИРОВАНО] Найти все документы пользователя с EAGER LOADING.
     */
    public function findAllByUser(int $userId): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.uploader_user', 'u')->addSelect('u')
            ->leftJoin('d.file', 'f')->addSelect('f')
            ->andWhere('d.uploader_user = :userId')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('userId', $userId)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * [ОПТИМИЗИРОВАНО] Найти все документы клиента с EAGER LOADING.
     */
    public function findAllByClient(User $client): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.uploader_user', 'u')->addSelect('u')
            ->leftJoin('d.file', 'f')->addSelect('f')
            ->leftJoin('d.company', 'c')->addSelect('c')
            ->where('d.uploader_user = :client')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('client', $client);

        if ($client->getCompany()) {
            $qb->orWhere('d.company = :company')
               ->setParameter('company', $client->getCompany());
        }

        return $qb->orderBy('d.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * [НОВЫЙ] Найти документы на модерации с EAGER LOADING.
     */
    public function findPendingModeration(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.uploader_user', 'u')->addSelect('u')
            ->leftJoin('d.company', 'c')->addSelect('c')
            ->leftJoin('d.application', 'a')->addSelect('a')
            ->where('d.status = :status')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('status', 'pending')
            ->orderBy('d.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * [НОВЫЙ] Найти документы заявки с EAGER LOADING.
     */
    public function findByApplication(Application $application): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.uploader_user', 'u')->addSelect('u')
            ->leftJoin('d.file', 'f')->addSelect('f')
            ->where('d.application = :application')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('application', $application)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * [НОВЫЙ] Найти документы компании с EAGER LOADING.
     */
    public function findByCompany(\App\User\Entity\Company $company): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.uploader_user', 'u')->addSelect('u')
            ->leftJoin('d.file', 'f')->addSelect('f')
            ->where('d.company = :company')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('company', $company)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * [НОВЫЙ] Найти документы по типу с EAGER LOADING.
     */
    public function findByType(string $type, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.uploader_user', 'u')->addSelect('u')
            ->leftJoin('d.file', 'f')->addSelect('f')
            ->where('d.type = :type')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('type', $type);

        if ($user) {
            $qb->andWhere('d.uploader_user = :user')
               ->setParameter('user', $user);
        }

        return $qb->orderBy('d.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * [НОВЫЙ] Получить статистику документов пользователя.
     */
    public function getUserDocumentStats(User $user): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('
                COUNT(d.id) as total_count,
                COUNT(CASE WHEN d.status = :pending THEN 1 END) as pending_count,
                COUNT(CASE WHEN d.status = :approved THEN 1 END) as approved_count,
                COUNT(CASE WHEN d.status = :rejected THEN 1 END) as rejected_count
            ')
            ->where('d.uploader_user = :user')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('pending', 'pending')
            ->setParameter('approved', 'approved')
            ->setParameter('rejected', 'rejected');

        return $qb->getQuery()->getSingleResult();
    }
}
