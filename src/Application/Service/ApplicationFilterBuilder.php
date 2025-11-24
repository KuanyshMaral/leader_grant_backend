<?php
// src/Application/Service/ApplicationFilterBuilder.php

namespace App\Application\Service;

use Doctrine\ORM\QueryBuilder;

/**
 * Builder для построения фильтров заявок.
 * Снижает цикломатическую сложность ApplicationService::listForUser.
 */
class ApplicationFilterBuilder
{
    private QueryBuilder $qb;
    
    public function __construct(QueryBuilder $qb)
    {
        $this->qb = $qb;
    }
    
    /**
     * Применяет фильтр по статусу.
     */
    public function filterByStatus(?string $status): self
    {
        if (empty($status)) {
            return $this;
        }
        
        if ($status === 'rejected') {
            $this->qb->andWhere('a.status = :status')
                     ->setParameter('status', 'rejected');
        } elseif ($status === 'archive') {
            $this->qb->andWhere('a.status IN (:statuses)')
                     ->setParameter('statuses', ['completed', 'archived']);
        } else {
            $this->qb->andWhere('a.status NOT IN (:statuses)')
                     ->setParameter('statuses', ['rejected', 'completed', 'archived']);
        }
        
        return $this;
    }
    
    /**
     * Применяет фильтр по типу продукта.
     */
    public function filterByProduct(?string $product): self
    {
        if (!empty($product)) {
            $this->qb->andWhere('a.product_type = :product')
                     ->setParameter('product', $product);
        }
        
        return $this;
    }
    
    /**
     * Применяет фильтр по банку.
     */
    public function filterByBank(?int $bankId): self
    {
        if (!empty($bankId)) {
            $this->qb->andWhere('a.bank = :bankId')
                     ->setParameter('bankId', $bankId);
        }
        
        return $this;
    }
    
    /**
     * Применяет фильтр по агенту.
     */
    public function filterByAgent(?int $agentId): self
    {
        if (!empty($agentId)) {
            $this->qb->andWhere('a.agent_user = :agentId')
                     ->setParameter('agentId', $agentId);
        }
        
        return $this;
    }
    
    /**
     * Применяет фильтр по диапазону дат.
     */
    public function filterByDateRange(?string $dateFrom, ?string $dateTo): self
    {
        if (!empty($dateFrom)) {
            $this->qb->andWhere('a.created_at >= :dateFrom')
                     ->setParameter('dateFrom', new \DateTime($dateFrom));
        }
        
        if (!empty($dateTo)) {
            $this->qb->andWhere('a.created_at <= :dateTo')
                     ->setParameter('dateTo', new \DateTime($dateTo));
        }
        
        return $this;
    }
    
    /**
     * Применяет фильтр по диапазону сумм.
     */
    public function filterByAmountRange(?float $amountMin, ?float $amountMax): self
    {
        if (!empty($amountMin)) {
            $this->qb->andWhere('a.amount >= :amountMin')
                     ->setParameter('amountMin', $amountMin);
        }
        
        if (!empty($amountMax)) {
            $this->qb->andWhere('a.amount <= :amountMax')
                     ->setParameter('amountMax', $amountMax);
        }
        
        return $this;
    }
    
    /**
     * Применяет поиск по ID или типу продукта.
     */
    public function filterBySearch(?string $search): self
    {
        if (!empty($search)) {
            $this->qb->andWhere('a.id = :search OR a.product_type LIKE :searchLike')
                     ->setParameter('search', $search)
                     ->setParameter('searchLike', '%' . $search . '%');
        }
        
        return $this;
    }
    
    /**
     * Применяет все фильтры из массива.
     */
    public function applyFilters(array $filters): self
    {
        return $this
            ->filterByStatus($filters['status'] ?? null)
            ->filterByProduct($filters['product'] ?? null)
            ->filterByBank($filters['bank_id'] ?? null)
            ->filterByAgent($filters['agent_id'] ?? null)
            ->filterByDateRange($filters['date_from'] ?? null, $filters['date_to'] ?? null)
            ->filterByAmountRange($filters['amount_min'] ?? null, $filters['amount_max'] ?? null)
            ->filterBySearch($filters['search'] ?? null);
    }
    
    /**
     * Возвращает QueryBuilder с примененными фильтрами.
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->qb;
    }
}
