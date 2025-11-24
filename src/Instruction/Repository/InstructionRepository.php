<?php
// src/Instruction/Repository/InstructionRepository.php

namespace App\Instruction\Repository;

use App\Instruction\Entity\Instruction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InstructionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Instruction::class);
    }

    /**
     * Find all published instructions ordered by order_index
     */
    public function findAllPublished(): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.is_published = :published')
            ->setParameter('published', true)
            ->orderBy('i.order_index', 'ASC')
            ->addOrderBy('i.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find instruction by slug
     */
    public function findOneBySlug(string $slug): ?Instruction
    {
        return $this->createQueryBuilder('i')
            ->where('i.slug = :slug')
            ->andWhere('i.is_published = :published')
            ->setParameter('slug', $slug)
            ->setParameter('published', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find instructions by category
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.category = :category')
            ->andWhere('i.is_published = :published')
            ->setParameter('category', $category)
            ->setParameter('published', true)
            ->orderBy('i.order_index', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
