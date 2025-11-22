<?php
// src/User/DTO/Components/FounderDTO.php

namespace App\User\DTO\Components;

use Symfony\Component\Validator\Constraints as Assert;

class FounderDTO
{
    #[Assert\NotBlank]
    public string $name;  // ФИО или Название организации

    #[Assert\NotBlank]
    public string $share; // Доля в %

    public ?string $inn = null; // ИНН

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'share' => $this->share,
            'inn' => $this->inn,
        ];
    }
}
