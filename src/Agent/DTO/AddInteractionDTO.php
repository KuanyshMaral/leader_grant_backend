<?php
// src/Agent/DTO/AddInteractionDTO.php

namespace App\Agent\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class AddInteractionDTO
{
    #[Assert\NotBlank(message: "Тип взаимодействия обязателен")]
    #[Assert\Choice(choices: ['call', 'meeting', 'email', 'other'], message: "Неверный тип взаимодействия")]
    public string $type;

    #[Assert\NotBlank(message: "Заметки обязательны")]
    #[Assert\Length(min: 10, max: 5000)]
    public string $notes;

    #[Assert\NotBlank(message: "Дата взаимодействия обязательна")]
    public string $interactionDate; // ISO 8601 format
}
