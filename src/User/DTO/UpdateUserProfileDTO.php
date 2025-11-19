<?php
// src/User/DTO/UpdateUserProfileDTO.php

namespace App\User\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserProfileDTO
{
    public ?string $fio = null;
    public ?string $phone = null;

    #[Assert\Choice(choices: ['male', 'female'], message: "Неверный пол")]
    public ?string $gender = null;

    public ?string $timezone = null;

    // Фото загружается отдельным запросом (как документ), сюда присылаем путь или ID
    // Или можно сделать отдельный эндпоинт POST /api/settings/avatar
}
