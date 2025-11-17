<?php
// src/Bank/Exception/BankNotFoundException.php

namespace App\Bank\Exception;

use App\Shared\Exception\AppBusinessExceptionInterface;

class BankNotFoundException extends \Exception implements AppBusinessExceptionInterface
{
    public function getStatusCode(): int
    {
        return 404; // Not Found
    }

    public function getApiErrorDetails(): array
    {
        return ['error' => 'Банк или партнер не найден'];
    }
}
