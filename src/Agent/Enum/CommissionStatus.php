<?php
// src/Agent/Enum/CommissionStatus.php

namespace App\Agent\Enum;

enum CommissionStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
    
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Ожидает оплаты',
            self::PAID => 'Оплачено',
            self::CANCELLED => 'Отменено',
        };
    }
    
    public function isPending(): bool
    {
        return $this === self::PENDING;
    }
    
    public function isPaid(): bool
    {
        return $this === self::PAID;
    }
    
    public function canBePaid(): bool
    {
        return $this === self::PENDING;
    }
}
