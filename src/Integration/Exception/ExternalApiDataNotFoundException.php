<?php
namespace App\Integration\Exception;

use App\Shared\Exception\AppBusinessExceptionInterface;

class ExternalApiDataNotFoundException extends \Exception implements AppBusinessExceptionInterface {
    public function getStatusCode(): int { return 404; }
    public function getApiErrorDetails(): array {
        return ['error' => 'Данные не найдены во внешнем источнике (ИНН или № закупки)'];
    }
}