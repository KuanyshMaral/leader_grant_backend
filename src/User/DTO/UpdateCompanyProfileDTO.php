<?php
// src/User/DTO/UpdateCompanyProfileDTO.php

namespace App\User\DTO;
use Symfony\Component\Validator\Constraints as Assert;
class UpdateCompanyProfileDTO {
    /**
     * @Assert\NotBlank
     * @Assert\Length(min=10, max=12)
     */
    public string $inn;

    /**
     * @Assert\NotBlank
     */
    public string $ogrn;

    public string $name;
    public string $full_name;
    public string $legal_address;
    public string $ceo_fio;
    public string $tax_system;

    /**
     * @Assert\Valid // "Провалиться" внутрь и валидировать вложенный DTO
     */
    public RequisitesDTO $requisites;
}

// Вспомогательный DTO для JSON-поля
class RequisitesDTO {
    /** @Assert\NotBlank */
    public string $bik;
    /** @Assert\NotBlank */
    public string $checking_account; // Расчетный счет
    /** @Assert\NotBlank */
    public string $corr_account;     // Корр. счет
}
?>
