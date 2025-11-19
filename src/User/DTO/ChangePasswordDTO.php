<?php
// src/User/DTO/ChangePasswordDTO.php

namespace App\User\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ChangePasswordDTO
{
    #[Assert\NotBlank]
    public string $oldPassword;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    public string $newPassword;
}
