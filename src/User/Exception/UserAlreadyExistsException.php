<?php
namespace App\User\Exception;

use App\Shared\Exception\AppBusinessExceptionInterface;

class UserAlreadyExistsException extends \Exception implements AppBusinessExceptionInterface {
    public function getStatusCode(): int { return 409; } // Conflict
    public function getApiErrorDetails(): array {
        return ['error' => 'Пользователь с таким email уже существует'];
    }
}