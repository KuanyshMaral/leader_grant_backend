<?php
namespace App\Application\Exception;

use App\Shared\Exception\AppBusinessExceptionInterface;

class ApplicationAccessDeniedException extends \Exception implements AppBusinessExceptionInterface {
    public function getStatusCode(): int { return 403; }
    public function getApiErrorDetails(): array {
        return ['error' => 'Доступ к заявке запрещен'];
    }
}