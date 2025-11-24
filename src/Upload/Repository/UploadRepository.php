<?php
// src/Upload/Repository/UploadRepository.php

namespace App\Upload\Repository;

use App\Upload\Entity\UploadedFile;
use App\Upload\Enum\FileContext;
use App\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UploadedFile>
 */
class UploadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UploadedFile::class);
    }

    public function save(UploadedFile $file): void
    {
        $this->getEntityManager()->persist($file);
        $this->getEntityManager()->flush();
    }

    public function remove(UploadedFile $file): void
    {
        $this->getEntityManager()->remove($file);
        $this->getEntityManager()->flush();
    }

    /**
     * Найти файл по ID.
     */
    public function findById(int $id): ?UploadedFile
    {
        return $this->find($id);
    }

    /**
     * Найти файл по имени в хранилище.
     */
    public function findByStoredFileName(string $fileName): ?UploadedFile
    {
        return $this->findOneBy(['storedFileName' => $fileName, 'isDeleted' => false]);
    }

    /**
     * Найти файлы пользователя.
     */
    public function findByUser(User $user, ?FileContext $context = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.uploadedBy = :user')
            ->andWhere('u.isDeleted = false')
            ->setParameter('user', $user)
            ->orderBy('u.uploadedAt', 'DESC');

        if ($context) {
            $qb->andWhere('u.context = :context')
               ->setParameter('context', $context);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Найти файлы по контексту.
     */
    public function findByContext(FileContext $context, ?string $contextId = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->where('f.context = :context')
            ->andWhere('f.isDeleted = false')
            ->setParameter('context', $context)
            ->orderBy('f.uploadedAt', 'DESC');

        if ($contextId !== null) {
            $qb->andWhere('f.contextId = :contextId')
               ->setParameter('contextId', $contextId);
        }

        return $qb->getQuery()->getResult();
    }
}
