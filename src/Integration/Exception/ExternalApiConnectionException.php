<?php
namespace App\Integration\Exception;

use App\Shared\Exception\AppBusinessExceptionInterface;

class ExternalApiConnectionException extends \Exception implements AppBusinessExceptionInterface {
    public function getStatusCode(): int { return 503; } // Service Unavailable
    public function getApiErrorDetails(): array {
        return ['error' => 'Внешний сервис (Checko/ЕИС) временно недоступен'];
    }
}