<?php
// src/User/DTO/UpdateUserProfileDTO.php

namespace App\User\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserProfileDTO
{
    #[Assert\Length(min: 2, max: 255)]
    public ?string $fio = null;
    public ?string $phone = null;

    #[Assert\Choice(choices: ['male', 'female'], message: "Неверный пол")]
    public ?string $gender = null;

    public ?string $timezone = null;
    #[Assert\Email]
    public ?string $email = null;

    /**
     * ID документа, который станет аватаром.
     * Файл сначала загружается через /api/documents/upload (docType='avatar'),
     * а сюда приходит только его ID.
     */
    public ?int $avatar_document_id = null;

    // Фото загружается отдельным запросом (как документ), сюда присылаем путь или ID
    // Или можно сделать отдельный эндпоинт POST /api/settings/avatar
}
