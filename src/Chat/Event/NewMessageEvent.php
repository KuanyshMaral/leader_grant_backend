<?php
// src/Chat/Event/NewMessageEvent.php

namespace App\Chat\Event;

/**
 * Событие, которое "кричит" в очередь,
 * когда Клиент/Агент отправляет новое сообщение,
 * требующее модерации Админа.
 */
class NewMessageEvent
{
    public function __construct(
        /**
         * ID нового сообщения со статусом 'pending'.
         */
        private readonly int $messageId
    ) {
    }

    public function getMessageId(): int
    {
        return $this->messageId;
    }
}
