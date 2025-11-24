<?php
// src/Application/Enum/ProductType.php

namespace App\Application\Enum;

enum ProductType: string
{
    case BANK_GUARANTEE = 'bank_guarantee';
    case CREDIT = 'credit';
    case FACTORING = 'factoring';
    case RKO = 'rko';
    
    public function label(): string
    {
        return match($this) {
            self::BANK_GUARANTEE => 'Банковская гарантия',
            self::CREDIT => 'Кредит',
            self::FACTORING => 'Факторинг',
            self::RKO => 'РКО',
        };
    }
    
    public function requiresCollateral(): bool
    {
        return in_array($this, [self::CREDIT, self::FACTORING]);
    }
    
    public function getDefaultTerm(): int
    {
        return match($this) {
            self::BANK_GUARANTEE => 365,
            self::CREDIT => 1095,
            self::FACTORING => 180,
            self::RKO => 0,
        };
    }
}
