<?php
// src/User/Event/UserRegisteredEvent.php
namespace App\User\Event;

// Это "сообщение", которое мы отправим в очередь
class UserRegisteredEvent
{
    public function __construct(
        private readonly int $userId
    ) {}

    public function getUserId(): int
    {
        return $this->userId;
    }
}
