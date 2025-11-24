<?php
// src/User/Enum/UserRole.php

namespace App\User\Enum;

enum UserRole: string
{
    case CLIENT = 'client';
    case AGENT = 'agent';
    case PARTNER = 'partner';
    case ADMIN = 'admin';
    
    public function label(): string
    {
        return match($this) {
            self::CLIENT => 'Клиент',
            self::AGENT => 'Агент',
            self::PARTNER => 'Партнер банка',
            self::ADMIN => 'Администратор',
        };
    }
    
    public function getSymfonyRole(): string
    {
        return 'ROLE_' . strtoupper($this->value);
    }
    
    public function canAccessApplication(\App\Application\Entity\Application $application, \App\User\Entity\User $user): bool
    {
        return match($this) {
            self::CLIENT => $application->getClientUser()->getId() === $user->getId(),
            self::AGENT => $application->getAgentUser()?->getId() === $user->getId(),
            self::PARTNER => $application->getBank()->getId() === $user->getBank()?->getId(),
            self::ADMIN => true,
        };
    }
    
    public function requiresAccreditation(): bool
    {
        return in_array($this, [self::AGENT, self::PARTNER]);
    }
}
