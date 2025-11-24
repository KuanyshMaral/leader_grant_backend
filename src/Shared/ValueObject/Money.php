<?php
// src/Shared/ValueObject/Money.php

namespace App\Shared\ValueObject;

class Money
{
    private function __construct(
        private readonly float $amount,
        private readonly string $currency = 'RUB'
    ) {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative');
        }
    }
    
    public static function fromFloat(float $amount, string $currency = 'RUB'): self
    {
        return new self($amount, $currency);
    }
    
    public static function fromString(string $amount, string $currency = 'RUB'): self
    {
        return new self((float) $amount, $currency);
    }
    
    public static function zero(string $currency = 'RUB'): self
    {
        return new self(0.0, $currency);
    }
    
    public function getAmount(): float
    {
        return $this->amount;
    }
    
    public function getCurrency(): string
    {
        return $this->currency;
    }
    
    public function add(Money $other): self
    {
        $this->ensureSameCurrency($other);
        return new self($this->amount + $other->amount, $this->currency);
    }
    
    public function subtract(Money $other): self
    {
        $this->ensureSameCurrency($other);
        return new self($this->amount - $other->amount, $this->currency);
    }
    
    public function multiply(float $multiplier): self
    {
        return new self($this->amount * $multiplier, $this->currency);
    }
    
    public function percentage(float $percent): self
    {
        return new self($this->amount * ($percent / 100), $this->currency);
    }
    
    public function isGreaterThan(Money $other): bool
    {
        $this->ensureSameCurrency($other);
        return $this->amount > $other->amount;
    }
    
    public function isLessThan(Money $other): bool
    {
        $this->ensureSameCurrency($other);
        return $this->amount < $other->amount;
    }
    
    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }
    
    public function format(): string
    {
        return number_format($this->amount, 2, '.', ' ') . ' ' . $this->currency;
    }
    
    private function ensureSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot operate on different currencies');
        }
    }
}
