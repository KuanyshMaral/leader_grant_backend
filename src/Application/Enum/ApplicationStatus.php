<?php
// src/Application/Enum/ApplicationStatus.php

namespace App\Application\Enum;

enum ApplicationStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case UNDER_REVIEW = 'under_review';
    case OFFER_RECEIVED = 'offer_received';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case COMPLETED = 'completed';
    case ARCHIVED = 'archived';
    
    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Черновик',
            self::PENDING => 'На рассмотрении',
            self::UNDER_REVIEW => 'В обработке',
            self::OFFER_RECEIVED => 'Получено предложение',
            self::APPROVED => 'Одобрено',
            self::REJECTED => 'Отклонено',
            self::COMPLETED => 'Завершено',
            self::ARCHIVED => 'В архиве',
        };
    }
    
    public function isActive(): bool
    {
        return !in_array($this, [self::REJECTED, self::COMPLETED, self::ARCHIVED]);
    }
    
    public function isTerminal(): bool
    {
        return in_array($this, [self::REJECTED, self::COMPLETED, self::ARCHIVED]);
    }
    
    public function canTransitionTo(self $newStatus): bool
    {
        // Из терминальных статусов нельзя переходить
        if ($this->isTerminal()) {
            return false;
        }
        
        return match($this) {
            self::DRAFT => in_array($newStatus, [self::PENDING, self::ARCHIVED]),
            self::PENDING => in_array($newStatus, [self::UNDER_REVIEW, self::REJECTED, self::ARCHIVED]),
            self::UNDER_REVIEW => in_array($newStatus, [self::OFFER_RECEIVED, self::APPROVED, self::REJECTED]),
            self::OFFER_RECEIVED => in_array($newStatus, [self::APPROVED, self::REJECTED]),
            self::APPROVED => in_array($newStatus, [self::COMPLETED]),
            default => false,
        };
    }
}
