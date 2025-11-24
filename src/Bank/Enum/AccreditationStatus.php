<?php
// src/Bank/Enum/AccreditationStatus.php

namespace App\Bank\Enum;

enum AccreditationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'На рассмотрении',
            self::APPROVED => 'Аккредитован',
            self::REJECTED => 'Отклонен',
        };
    }
    
    public function isApproved(): bool
    {
        return $this === self::APPROVED;
    }
    
    public function canBeModerated(): bool
    {
        return $this === self::PENDING;
    }
}
