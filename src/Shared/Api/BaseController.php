<?php

namespace App\Shared\Api;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Base controller with request/response logging
 * All API controllers should extend this class for consistent tracing
 */
abstract class BaseController extends AbstractController
{
    public function __construct(
        protected readonly LoggerInterface $logger
    ) {}
    
    /**
     * Log incoming API request
     */
    protected function logRequest(Request $request, string $endpoint): void
    {
        $user = $request->attributes->get('user');
        
        $this->logger->info('API Request', [
            'endpoint' => $endpoint,
            'method' => $request->getMethod(),
            'user_id' => $user?->getId(),
            'user_role' => $user?->getRole(),
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
            'query_params' => $request->query->all(),
            'has_body' => $request->getContent() !== ''
        ]);
    }
    
    /**
     * Log API response
     */
    protected function logResponse(string $endpoint, int $statusCode, array $context = []): void
    {
        $level = $statusCode >= 400 ? 'warning' : 'info';
        
        $this->logger->$level('API Response', array_merge([
            'endpoint' => $endpoint,
            'status_code' => $statusCode
        ], $context));
    }
    
    /**
     * Log API error with full context
     */
    protected function logApiError(string $endpoint, \Throwable $e, array $context = []): void
    {
        $this->logger->error('API Error', array_merge([
            'endpoint' => $endpoint,
            'error' => $e->getMessage(),
            'error_class' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ], $context));
    }
    
    /**
     * Create error response and log it
     */
    protected function errorResponse(string $endpoint, string $message, int $statusCode = 400, array $context = []): JsonResponse
    {
        $this->logger->warning('API Error Response', array_merge([
            'endpoint' => $endpoint,
            'message' => $message,
            'status_code' => $statusCode
        ], $context));
        
        return new JsonResponse(['error' => $message], $statusCode);
    }
}
