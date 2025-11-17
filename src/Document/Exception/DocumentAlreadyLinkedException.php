<?php
// src/Document/Exception/DocumentAlreadyLinkedException.php
namespace App\Document\Exception;

use App\Shared\Exception\AppBusinessExceptionInterface;

class DocumentAlreadyLinkedException extends \Exception implements AppBusinessExceptionInterface {
    public function getStatusCode(): int { return 409; } // Conflict
    public function getApiErrorDetails(): array {
        return ['error' => 'Этот документ уже был использован (привязан)'];
    }
}
