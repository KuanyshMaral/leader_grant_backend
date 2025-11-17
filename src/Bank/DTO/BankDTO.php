<?php
// src/Bank/DTO/BankDTO.php

namespace App\Bank\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class BankDTO
{
    #[Assert\NotBlank(message: "Название банка обязательно")]
    public string $name;

    public ?string $logo_path;

    #[Assert\Type("array")]
    public array $conditions; // (Весь JSON с тарифами, лимитами и т.д.)
}
