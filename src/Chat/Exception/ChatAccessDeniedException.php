<?php
namespace App\Chat\Exception;

use App\Shared\Exception\AppBusinessExceptionInterface;

class ChatAccessDeniedException extends \Exception implements AppBusinessExceptionInterface {
    public function getStatusCode(): int { return 403; }
    public function getApiErrorDetails(): array {
        return ['error' => 'Доступ к этому чату запрещен'];
    }
}