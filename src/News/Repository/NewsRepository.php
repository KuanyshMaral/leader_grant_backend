<?php
// src/News/Repository/NewsRepository.php

namespace App\News\Repository;

use App\News\Entity\News;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<News>
 * 
 * ОПТИМИЗИРОВАНО: Добавлены методы для пагинации и фильтрации новостей
 */
class NewsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, News::class);
    }

    public function save(News $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Находит опубликованные новости.
     */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.isPublished = :val')
            ->setParameter('val', true)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * [НОВЫЙ] Находит опубликованные новости с пагинацией.
     */
    public function findPublishedPaginated(int $page = 1, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('n.createdAt', 'DESC');

        // Подсчет total
        $countQb = clone $qb;
        $total = $countQb->select('COUNT(n.id)')->getQuery()->getSingleScalarResult();

        // Пагинация
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return [
            'data' => $qb->getQuery()->getResult(),
            'total' => (int) $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    /**
     * [НОВЫЙ] Находит последние N новостей.
     */
    public function findLatest(int $limit = 5): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
