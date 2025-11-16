<?php
// src/Shared/Exception/AppBusinessExceptionInterface.php

namespace App\Shared\Exception;

/**
 * Абстракция для ВСЕХ наших "ожидаемых" бизнес-исключений.
 * (Наследуемся от \Throwable, чтобы это все еще было "исключением")
 */
interface AppBusinessExceptionInterface extends \Throwable
{
    /**
     * Возвращает HTTP-статус, который должен увидеть клиент.
     * (404, 403, 409 и т.д.)
     */
    public function getStatusCode(): int;

    /**
     * Возвращает массив с деталями для JSON-ответа.
     */
    public function getApiErrorDetails(): array;
}