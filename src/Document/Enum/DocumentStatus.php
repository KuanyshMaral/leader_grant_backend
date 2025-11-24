<?php
// src/Document/Enum/DocumentStatus.php

namespace App\Document\Enum;

enum DocumentStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'На модерации',
            self::APPROVED => 'Одобрен',
            self::REJECTED => 'Отклонен',
        };
    }
    
    public function isPending(): bool
    {
        return $this === self::PENDING;
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
