<?php
// src/User/DTO/Components/ManagementDTO.php

namespace App\User\DTO\Components;

use Symfony\Component\Validator\Constraints as Assert;

class ManagementDTO
{
    #[Assert\NotBlank]
    public string $position; // Должность

    #[Assert\NotBlank]
    public string $fio;      // ФИО

    public ?string $birth_date = null; // Дата рождения

    public ?string $passport = null;   // Серия номер

    // Метод для превращения в массив (для БД)
    public function toArray(): array
    {
        return [
            'position' => $this->position,
            'fio' => $this->fio,
            'birth_date' => $this->birth_date,
            'passport' => $this->passport,
        ];
    }
}
