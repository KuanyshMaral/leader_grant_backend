<?php
// src/Chat/Event/MessageApprovedEvent.php

namespace App\Chat\Event;

/**
 * Событие (Сообщение), которое "кричит" в очередь,
 * когда Админ одобрил сообщение в чате.
 */
class MessageApprovedEvent
{
    public function __construct(
        /**
         * ID сообщения, которое было одобрено.
         */
        private readonly int $messageId
    ) {
    }

    public function getMessageId(): int
    {
        return $this->messageId;
    }
}
