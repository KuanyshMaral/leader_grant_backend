<?php
// src/Shared/ValueObject/PhoneNumber.php

namespace App\Shared\ValueObject;

class PhoneNumber
{
    private function __construct(
        private readonly string $value
    ) {
        if (!$this->isValid($value)) {
            throw new \InvalidArgumentException("Invalid phone number: {$value}");
        }
    }
    
    public static function fromString(string $phone): self
    {
        // Нормализация: убираем все кроме цифр и +
        $normalized = preg_replace('/[^\d+]/', '', $phone);
        return new self($normalized);
    }
    
    public function getValue(): string
    {
        return $this->value;
    }
    
    public function getFormatted(): string
    {
        // Форматирование для Казахстана: +7 (XXX) XXX-XX-XX
        if (preg_match('/^\+7(\d{3})(\d{3})(\d{2})(\d{2})$/', $this->value, $matches)) {
            return "+7 ({$matches[1]}) {$matches[2]}-{$matches[3]}-{$matches[4]}";
        }
        return $this->value;
    }
    
    public function equals(PhoneNumber $other): bool
    {
        return $this->value === $other->value;
    }
    
    public function __toString(): string
    {
        return $this->value;
    }
    
    private function isValid(string $phone): bool
    {
        // Для Казахстана: +7XXXXXXXXXX (11 цифр с кодом)
        return preg_match('/^\+7\d{10}$/', $phone) === 1;
    }
}
