<?php
// src/Document/Exception/DocumentAccessDeniedException.php
namespace App\Document\Exception;

use App\Shared\Exception\AppBusinessExceptionInterface;

class DocumentAccessDeniedException extends \Exception implements AppBusinessExceptionInterface {
    public function getStatusCode(): int { return 403; } // Forbidden
    public function getApiErrorDetails(): array {
        return ['error' => 'Доступ к этому документу запрещен'];
    }
}
