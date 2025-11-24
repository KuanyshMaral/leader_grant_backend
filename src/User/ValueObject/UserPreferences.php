<?php
// src/User/ValueObject/UserPreferences.php

namespace App\User\ValueObject;

/**
 * Value Object для пользовательских настроек.
 * Инкапсулирует логику работы с JSON preferences.
 */
class UserPreferences
{
    private array $data;
    
    private function __construct(array $data)
    {
        $this->data = $data;
    }
    
    public static function fromArray(array $data): self
    {
        return new self($data);
    }
    
    public static function fromJson(?string $json): self
    {
        if (empty($json)) {
            return new self([]);
        }
        
        $data = json_decode($json, true);
        return new self($data ?? []);
    }
    
    public static function default(): self
    {
        return new self([
            'notifications' => [
                'email' => true,
                'sms' => false,
                'push' => true,
            ],
            'language' => 'ru',
            'theme' => 'light',
        ]);
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }
    
    public function set(string $key, mixed $value): self
    {
        $newData = $this->data;
        $newData[$key] = $value;
        return new self($newData);
    }
    
    public function getNotificationSettings(): array
    {
        return $this->get('notifications', [
            'email' => true,
            'sms' => false,
            'push' => true,
        ]);
    }
    
    public function getLanguage(): string
    {
        return $this->get('language', 'ru');
    }
    
    public function getTheme(): string
    {
        return $this->get('theme', 'light');
    }
    
    public function toArray(): array
    {
        return $this->data;
    }
    
    public function toJson(): string
    {
        return json_encode($this->data);
    }
}
