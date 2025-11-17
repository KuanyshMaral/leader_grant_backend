<?php
// src/User/Event/AccreditationSubmittedEvent.php
namespace App\User\Event;

class AccreditationSubmittedEvent
{
    public function __construct(
        private readonly int $userId,
        private readonly int $companyId
    ) {}

    public function getUserId(): int { return $this->userId; }
    public function getCompanyId(): int { return $this->companyId; }
}
