<?php
// src/Upload/Exception/FileNotFoundException.php

namespace App\Upload\Exception;

use App\Shared\Exception\AppBusinessExceptionInterface;

class FileNotFoundException extends \Exception implements AppBusinessExceptionInterface
{
    public function __construct(string $message = 'Файл не найден')
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return 404; // Not Found
    }

    public function getApiErrorDetails(): array
    {
        return ['error' => $this->getMessage()];
    }
}
