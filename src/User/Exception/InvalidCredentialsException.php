<?php
namespace App\User\Exception;

use App\Shared\Exception\AppBusinessExceptionInterface;

class InvalidCredentialsException extends \Exception implements AppBusinessExceptionInterface {
    public function getStatusCode(): int { return 401; } // Unauthorized
    public function getApiErrorDetails(): array {
        return ['error' => 'Неверный email или пароль'];
    }
}