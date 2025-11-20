<?php
// src/User/DTO/CreatePartnerDTO.php

namespace App\User\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreatePartnerDTO
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    public string $password;

    #[Assert\NotBlank]
    public string $fio;

    #[Assert\NotBlank]
    public string $phone;

    #[Assert\NotBlank]
    public int $bank_id; // К какому банку привязать сотрудника
}
