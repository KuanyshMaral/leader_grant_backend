<?php
// src/Chat/Enum/ModerationStatus.php

namespace App\Chat\Enum;

enum ModerationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'На модерации',
            self::APPROVED => 'Одобрено',
            self::REJECTED => 'Отклонено',
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
    
    public function requiresModeration(): bool
    {
        return $this === self::PENDING;
    }
}
