<?php
// src/Document/Exception/DocumentNotFoundException.php
namespace App\Document\Exception;

use App\Shared\Exception\AppBusinessExceptionInterface;

class DocumentNotFoundException extends \Exception implements AppBusinessExceptionInterface {
    public function __construct(string $message = 'Документ не найден', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int { return 404; } // Not Found
    public function getApiErrorDetails(): array {
        return ['error' => $this->message];
    }
}
