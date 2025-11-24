<?php
// src/Bank/Enum/BankStatus.php

namespace App\Bank\Enum;

enum BankStatus: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case ARCHIVED = 'archived';
    
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Активен',
            self::SUSPENDED => 'Приостановлен',
            self::ARCHIVED => 'Архивирован',
        };
    }
    
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
    
    public function canAcceptApplications(): bool
    {
        return $this === self::ACTIVE;
    }
}
