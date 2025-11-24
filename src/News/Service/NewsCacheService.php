<?php
// src/News/Service/NewsCacheService.php

namespace App\News\Service;

use App\News\Repository\NewsRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Сервис для кэширования новостей.
 * Новости редко меняются, поэтому идеально подходят для кэширования.
 */
class NewsCacheService
{
    private const CACHE_TTL = 1800; // 30 минут
    private const CACHE_KEY_PUBLISHED = 'news.published';
    private const CACHE_KEY_LATEST = 'news.latest.';
    
    public function __construct(
        private readonly NewsRepository $newsRepository,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger
    ) {
    }
    
    /**
     * Получить опубликованные новости (с кэшированием).
     */
    public function getPublishedNews(): array
    {
        $this->logger->debug('Fetching published news from cache');
        
        return $this->cache->get(self::CACHE_KEY_PUBLISHED, function (ItemInterface $item) {
            $item->expiresAfter(self::CACHE_TTL);
            $news = $this->newsRepository->findPublished();
            
            $this->logger->info('Published news cache refreshed', [
                'count' => count($news),
                'ttl' => self::CACHE_TTL
            ]);
            
            return $news;
        });
    }
    
    /**
     * Получить последние N новостей (с кэшированием).
     */
    public function getLatestNews(int $limit = 5): array
    {
        $cacheKey = self::CACHE_KEY_LATEST . $limit;
        
        $this->logger->debug('Fetching latest news from cache', ['limit' => $limit]);
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($limit) {
            $item->expiresAfter(self::CACHE_TTL);
            $news = $this->newsRepository->findLatest($limit);
            
            $this->logger->info('Latest news cache refreshed', [
                'limit' => $limit,
                'count' => count($news)
            ]);
            
            return $news;
        });
    }
    
    /**
     * Инвалидировать кэш новостей.
     * Вызывается при создании/обновлении/удалении новости.
     */
    public function invalidateCache(): void
    {
        $this->cache->delete(self::CACHE_KEY_PUBLISHED);
        
        // Инвалидируем кэш для разных лимитов
        for ($i = 1; $i <= 10; $i++) {
            $this->cache->delete(self::CACHE_KEY_LATEST . $i);
        }
        
        $this->logger->info('News cache invalidated');
    }
}
