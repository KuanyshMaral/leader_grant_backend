<?php
// src/Shared/Service/PaginationHelper.php

namespace App\Shared\Service;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Хелпер для оптимизированной пагинации с использованием Doctrine Paginator.
 * Устраняет проблему клонирования QueryBuilder для подсчета total.
 */
class PaginationHelper
{
    /**
     * Создает пагинированный результат с использованием Doctrine Paginator.
     * 
     * @param QueryBuilder $qb Query Builder с основным запросом
     * @param int $page Номер страницы (начиная с 1)
     * @param int $limit Количество элементов на странице
     * @return array ['data' => array, 'total' => int, 'page' => int, 'limit' => int, 'pages' => int]
     */
    public static function paginate(QueryBuilder $qb, int $page = 1, int $limit = 20): array
    {
        // Применяем пагинацию к QueryBuilder
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);
        
        // Используем Doctrine Paginator для эффективного подсчета
        $paginator = new Paginator($qb, $fetchJoinCollection = true);
        
        $total = count($paginator);
        $pages = (int) ceil($total / $limit);
        
        return [
            'data' => iterator_to_array($paginator),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => $pages,
        ];
    }
    
    /**
     * Создает пагинированный результат из массива.
     * Полезно когда данные уже загружены из кэша.
     */
    public static function paginateArray(array $items, int $page = 1, int $limit = 20): array
    {
        $total = count($items);
        $pages = (int) ceil($total / $limit);
        $offset = ($page - 1) * $limit;
        
        $data = array_slice($items, $offset, $limit);
        
        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => $pages,
        ];
    }
}
