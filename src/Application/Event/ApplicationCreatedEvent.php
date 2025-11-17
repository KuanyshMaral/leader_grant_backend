<?php
// src/Application/Event/ApplicationCreatedEvent.php

namespace App\Application\Event;

/**
 * Событие (Сообщение), которое "кричит" в очередь,
 * когда одна или несколько заявок были созданы.
 *
 * Мы передаем массив ID, так как ApplicationService
 * может создать несколько заявок за раз (по одной на банк).
 */
class ApplicationCreatedEvent
{
    /**
     * @param int[] $applicationIds
     */
    public function __construct(
        private readonly array $applicationIds,
        private readonly int $clientId
    ) {
    }

    /**
     * @return int[]
     */
    public function getApplicationIds(): array
    {
        return $this->applicationIds;
    }

    public function getClientId(): int
    {
        return $this->clientId;
    }
}
