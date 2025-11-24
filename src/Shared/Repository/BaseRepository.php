<?php

namespace App\Shared\Repository;

use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Base repository with logger support
 * All repositories should extend this class for consistent logging
 */
abstract class BaseRepository extends EntityRepository
{
    protected LoggerInterface $logger;
    
    public function __construct($em, $class)
    {
        parent::__construct($em, $class);
        // Initialize with NullLogger by default to avoid errors
        $this->logger = new NullLogger();
    }
    
    /**
     * Set logger instance (called by Symfony DI)
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
    
    /**
     * Log a successful database query operation
     */
    protected function logQuery(string $operation, array $context = []): void
    {
        $this->logger->debug("Repository operation: {$operation}", array_merge([
            'repository' => static::class
        ], $context));
    }
    
    /**
     * Log a database error with full context
     */
    protected function logError(string $operation, \Throwable $e, array $context = []): void
    {
        $this->logger->error("Repository error: {$operation}", array_merge([
            'repository' => static::class,
            'error' => $e->getMessage(),
            'error_class' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], $context));
    }
    
    /**
     * Log a warning (e.g., empty results, deprecated usage)
     */
    protected function logWarning(string $message, array $context = []): void
    {
        $this->logger->warning($message, array_merge([
            'repository' => static::class
        ], $context));
    }
    
    /**
     * Log an info message (e.g., successful save, delete)
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $this->logger->info($message, array_merge([
            'repository' => static::class
        ], $context));
    }
}
