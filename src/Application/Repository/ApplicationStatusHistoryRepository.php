<?php
// src/Application/Repository/ApplicationStatusHistoryRepository.php

namespace App\Application\Repository;

use App\Application\Entity\ApplicationStatusHistory;
use App\Application\Entity\Application;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ApplicationStatusHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApplicationStatusHistory::class);
    }

    public function save(ApplicationStatusHistory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Получить историю статусов для заявки
     */
    public function findByApplication(Application $application): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.application = :application')
            ->setParameter('application', $application)
            ->orderBy('h.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
