<?php
// src/User/DTO/UpdateCompanyProfileDTO.php

namespace App\User\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateCompanyProfileDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 12)]
    public string $inn;

    #[Assert\NotBlank]
    public string $ogrn;

    public ?string $kpp = null;
    public string $name;
    public string $full_name;
    public string $legal_address;
    public ?string $actual_address = null;

    public ?string $web_site = null;
    public ?string $office_phone = null;
    public ?string $email = null;
    public ?string $ceo_fio = null;

    #[Assert\NotBlank]
    public string $tax_system;
    public ?string $vat_rate = null;

    public ?string $registration_date = null;
    public ?string $authorized_capital = null;
    public ?string $paid_capital = null;
    public ?int $employee_count = null;
    public ?int $contract_count = null;

    // --- СПИСКИ (Просто массивы) ---

    #[Assert\Type("array")]
    public array $requisites = [];

    #[Assert\Type("array")]
    public array $management = [];

    #[Assert\Type("array")]
    public array $founders = [];

    #[Assert\Type("array")]
    public array $licenses = [];

    #[Assert\Type("array")]
    public array $contact_persons = [];

    #[Assert\Type("array")]
    public array $etp_accounts = [];
}
