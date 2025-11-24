<?php
namespace App\User\Exception;

use App\Shared\Exception\AppBusinessExceptionInterface;

class CompanyAlreadyExistsException extends \Exception implements AppBusinessExceptionInterface {
    public function getStatusCode(): int { return 409; } // Conflict
    public function getApiErrorDetails(): array {
        return ['error' => $this->getMessage()];
    }
}
