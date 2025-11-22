<?php
// src/User/DTO/Components/RequisiteDTO.php

namespace App\User\DTO\Components;

use Symfony\Component\Validator\Constraints as Assert;

class RequisiteDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 9, max: 9)]
    public string $bik;

    #[Assert\NotBlank]
    public string $bank_name;

    #[Assert\NotBlank]
    public string $checking_account; // Расчетный счет

    #[Assert\NotBlank]
    public string $corr_account;     // Корр. счет

    public function toArray(): array
    {
        return [
            'bik' => $this->bik,
            'bank_name' => $this->bank_name,
            'checking_account' => $this->checking_account,
            'corr_account' => $this->corr_account,
        ];
    }
}
