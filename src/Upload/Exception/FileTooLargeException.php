<?php
// src/Upload/Exception/FileTooLargeException.php

namespace App\Upload\Exception;

use App\Shared\Exception\AppBusinessExceptionInterface;

class FileTooLargeException extends \Exception implements AppBusinessExceptionInterface
{
    public function __construct(string $message = 'Файл слишком большой')
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return 400; // Bad Request
    }

    public function getApiErrorDetails(): array
    {
        return ['error' => $this->getMessage()];
    }
}
