<?php
// src/Document/Repository/DocumentRepository.php

namespace App\Document\Repository;

use App\Document\Entity\Document;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
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
     * Найти все документы, загруженные конкретным пользователем.
     */
    public function findAllByUser(int $userId): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.uploader_user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('d.createdAt', 'DESC') // <--- ИСПРАВЛЕНО (было created_at)
            ->getQuery()
            ->getResult();
    }
}
