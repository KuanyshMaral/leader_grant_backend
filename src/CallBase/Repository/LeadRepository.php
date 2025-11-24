<?php

namespace App\CallBase\Repository;

use App\CallBase\Entity\Lead;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lead>
 */
class LeadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lead::class);
    }

    /**
     * Get leads for specific agent (assigned or unassigned pool)
     */
    public function findForAgent(int $agentUserId): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.assignedTo = :agentId OR l.assignedTo IS NULL')
            ->setParameter('agentId', $agentUserId)
            ->orderBy('
                CASE l.status 
                    WHEN :new THEN 1 
                    WHEN :process THEN 2 
                    WHEN :success THEN 3 
                    WHEN :rejected THEN 4 
                END', 'ASC')
            ->addOrderBy('l.createdAt', 'DESC')
            ->setParameter('new', 'new')
            ->setParameter('process', 'process')
            ->setParameter('success', 'success')
            ->setParameter('rejected', 'rejected')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Find lead by INN (prevent duplicates)
     */
    public function findByInn(string $inn): ?Lead
    {
        return $this->findOneBy(['inn' => $inn]);
    }
    
    /**
     * Find all unconverted successful leads for analytics
     */
    public function findUnconvertedSuccessLeads(): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.status = :success')
            ->andWhere('l.convertedToApplication IS NULL')
            ->setParameter('success', 'success')
            ->getQuery()
            ->getResult();
    }
}
