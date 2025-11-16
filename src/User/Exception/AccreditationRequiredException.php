<?php
namespace App\User\Exception;

use App\Shared\Exception\AppBusinessExceptionInterface;

class AccreditationRequiredException extends \Exception implements AppBusinessExceptionInterface {
    public function getStatusCode(): int { return 403; } // Forbidden
    public function getApiErrorDetails(): array {
        return ['error' => 'Действие запрещено. Требуется аккредитация.'];
    }
}