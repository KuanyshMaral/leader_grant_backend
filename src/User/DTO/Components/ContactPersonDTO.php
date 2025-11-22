<?php
// src/User/DTO/Components/ContactPersonDTO.php

namespace App\User\DTO\Components;

use Symfony\Component\Validator\Constraints as Assert;

class ContactPersonDTO
{
    public string $fio = '';
    public string $position = '';

    #[Assert\Email]
    public ?string $email = null;

    public string $phone = '';

    public function toArray(): array
    {
        return [
            'fio' => $this->fio,
            'position' => $this->position,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }
}
