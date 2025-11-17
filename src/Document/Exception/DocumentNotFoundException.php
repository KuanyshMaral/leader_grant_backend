<?php
// src/Document/Exception/DocumentNotFoundException.php
namespace App\Document\Exception;

use App\Shared\Exception\AppBusinessExceptionInterface;

class DocumentNotFoundException extends \Exception implements AppBusinessExceptionInterface {
    public function getStatusCode(): int { return 404; } // Not Found
    public function getApiErrorDetails(): array {
        return ['error' => 'Документ не найден'];
    }
}
