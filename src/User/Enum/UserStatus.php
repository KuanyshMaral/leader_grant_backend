<?php
// src/User/Enum/UserStatus.php

namespace App\User\Enum;

enum UserStatus: string
{
    case PENDING_REVIEW = 'pending_review';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case REJECTED = 'rejected';
    
    public function label(): string
    {
        return match($this) {
            self::PENDING_REVIEW => 'На проверке',
            self::ACTIVE => 'Активен',
            self::SUSPENDED => 'Приостановлен',
            self::REJECTED => 'Отклонен',
        };
    }
    
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
    
    public function canLogin(): bool
    {
        return in_array($this, [self::ACTIVE, self::PENDING_REVIEW]);
    }
}
