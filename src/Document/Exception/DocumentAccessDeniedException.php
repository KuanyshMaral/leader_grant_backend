<?php
// src/Document/Exception/DocumentAccessDeniedException.php
namespace App\Document\Exception;

use App\Shared\Exception\AppBusinessExceptionInterface;

class DocumentAccessDeniedException extends \Exception implements AppBusinessExceptionInterface {
    public function __construct(string $message = 'Доступ к этому документу запрещен', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int { return 403; } // Forbidden
    public function getApiErrorDetails(): array {
        return ['error' => $this->message];
    }
}
