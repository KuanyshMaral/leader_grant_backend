<?php
// src/Bank/Service/BankCacheService.php

namespace App\Bank\Service;

use App\Bank\Repository\BankRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Сервис для кэширования справочных данных банков.
 * Уменьшает нагрузку на БД для часто запрашиваемых данных.
 */
class BankCacheService
{
    private const CACHE_TTL = 3600; // 1 час
    private const CACHE_KEY_ALL = 'banks.all';
    private const CACHE_KEY_ACTIVE = 'banks.active';
    
    public function __construct(
        private readonly BankRepository $bankRepository,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger
    ) {
    }
    
    /**
     * Получить все банки (с кэшированием).
     */
    public function getAllBanks(): array
    {
        $this->logger->debug('Fetching all banks from cache');
        
        return $this->cache->get(self::CACHE_KEY_ALL, function (ItemInterface $item) {
            $item->expiresAfter(self::CACHE_TTL);
            $banks = $this->bankRepository->findAll();
            
            $this->logger->info('Banks cache refreshed', [
                'count' => count($banks),
                'ttl' => self::CACHE_TTL
            ]);
            
            return $banks;
        });
    }
    
    /**
     * Получить активные банки (с кэшированием).
     */
    public function getActiveBanks(): array
    {
        $this->logger->debug('Fetching active banks from cache');
        
        return $this->cache->get(self::CACHE_KEY_ACTIVE, function (ItemInterface $item) {
            $item->expiresAfter(self::CACHE_TTL);
            $banks = $this->bankRepository->findActive();
            
            $this->logger->info('Active banks cache refreshed', [
                'count' => count($banks),
                'ttl' => self::CACHE_TTL
            ]);
            
            return $banks;
        });
    }
    
    /**
     * Инвалидировать кэш банков.
     * Вызывается при создании/обновлении/удалении банка.
     */
    public function invalidateCache(): void
    {
        $this->cache->delete(self::CACHE_KEY_ALL);
        $this->cache->delete(self::CACHE_KEY_ACTIVE);
        
        $this->logger->info('Bank cache invalidated');
    }
}
