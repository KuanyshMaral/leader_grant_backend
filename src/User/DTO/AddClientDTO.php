<?php
// src/User/DTO/AddClientDTO.php

namespace App\User\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class AddClientDTO
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    public string $fio;

    public ?string $phone = null;

    // Можно сразу указать ИНН компании, если агент его знает
    #[Assert\Length(min: 10, max: 12)]
    public ?string $inn = null;
}
