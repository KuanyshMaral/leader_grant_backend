<?php
// src/Upload/Exception/InvalidFileTypeException.php

namespace App\Upload\Exception;

use App\Shared\Exception\AppBusinessExceptionInterface;

class InvalidFileTypeException extends \Exception implements AppBusinessExceptionInterface
{
    public function __construct(string $message = 'Недопустимый тип файла')
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
