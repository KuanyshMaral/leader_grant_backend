<?php
// src/Shared/Infrastructure/ApiExceptionListener.php

namespace App\Shared\Infrastructure;

use App\Shared\Exception\AppBusinessExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Psr\Log\LoggerInterface;

class ApiExceptionListener {

    // 1. Мы добавляем $isDebug (в Symfony это '%kernel.debug%', в Laravel config('app.debug'))
    public function __construct(
        private LoggerInterface $logger,
        private bool $isDebug // <--- ВОТ ЭТО ИЗМЕНЕНИЕ
    ) {}

    public function onKernelException(ExceptionEvent $event): void {

        $exception = $event->getThrowable();
        $responseBody = [];
        $statusCode = 500;

        // 2. Наша основная "чистая" логика (как и была)
        if ($exception instanceof AppBusinessExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $responseBody = $exception->getApiErrorDetails();

        } else if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $responseBody = [
                'error' => 'Ошибка запроса (Bad Request)',
                'detail' => $exception->getMessage()
            ];

        } else {
            $statusCode = 500;
            $responseBody = ['error' => 'Критическая внутренняя ошибка сервера'];
        }

        // 3. !!! ВОТ РЕШЕНИЕ ВАШЕЙ ПРОБЛЕМЫ !!!
        // Если мы в режиме отладки (APP_ENV=dev), мы ДОБАВЛЯЕМ
        // полную отладочную информацию к ЛЮБОМУ ответу.
        if ($this->isDebug) {
            $responseBody['debug'] = [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        // 4. Логгируем только 500-е ошибки (как и раньше)
        if ($statusCode === 500) {
            $this->logger->error(
                'Необработанное исключение: ' . $exception->getMessage(),
                ['trace' => $exception->getTraceAsString()]
            );
        }

        $event->setResponse(new JsonResponse($responseBody, $statusCode));
    }
}
