<?php
// src/Shared/ValueObject/INN.php

namespace App\Shared\ValueObject;

class INN
{
    private function __construct(
        private readonly string $value
    ) {
        if (!$this->isValid($value)) {
            throw new \InvalidArgumentException("Invalid INN: {$value}");
        }
    }
    
    public static function fromString(string $inn): self
    {
        return new self(trim($inn));
    }
    
    public function getValue(): string
    {
        return $this->value;
    }
    
    public function isIndividual(): bool
    {
        return strlen($this->value) === 12;
    }
    
    public function isLegalEntity(): bool
    {
        return strlen($this->value) === 12;
    }
    
    public function equals(INN $other): bool
    {
        return $this->value === $other->value;
    }
    
    public function __toString(): string
    {
        return $this->value;
    }
    
    private function isValid(string $inn): bool
    {
        // Для Казахстана ИИН/БИН - 12 цифр
        return preg_match('/^\d{12}$/', $inn) === 1;
    }
}
