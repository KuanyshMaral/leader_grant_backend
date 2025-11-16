<?php
namespace App\Application\Exception;

use App\Shared\Exception\AppBusinessExceptionInterface;

class ApplicationNotFoundException extends \Exception implements AppBusinessExceptionInterface {
    public function getStatusCode(): int { return 404; }
    public function getApiErrorDetails(): array {
        return ['error' => 'Заявка не найдена'];
    }
}