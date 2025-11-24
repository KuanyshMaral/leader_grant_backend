<?php
// src/Shared/EventListener/RateLimitListener.php

namespace App\Shared\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * Event Listener для применения rate limiting к API запросам.
 */
class RateLimitListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly RateLimiterFactory $apiLimiter,
        private readonly RateLimiterFactory $authLimiter
    ) {
    }
    
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }
    
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        
        $request = $event->getRequest();
        $path = $request->getPathInfo();
        
        // Применяем rate limiting только к API endpoints
        if (!str_starts_with($path, '/api/')) {
            return;
        }
        
        // Специальный лимит для аутентификации
        if (str_starts_with($path, '/api/auth/')) {
            $limiter = $this->authLimiter->create($request->getClientIp());
        } else {
            $limiter = $this->apiLimiter->create($request->getClientIp());
        }
        
        $limit = $limiter->consume(1);
        
        if (!$limit->isAccepted()) {
            throw new TooManyRequestsHttpException(
                $limit->getRetryAfter()->getTimestamp() - time(),
                'Too many requests. Please try again later.'
            );
        }
        
        // Добавляем заголовки с информацией о лимитах
        $event->getResponse()?->headers->set('X-RateLimit-Limit', (string) $limit->getLimit());
        $event->getResponse()?->headers->set('X-RateLimit-Remaining', (string) $limit->getRemainingTokens());
    }
}
