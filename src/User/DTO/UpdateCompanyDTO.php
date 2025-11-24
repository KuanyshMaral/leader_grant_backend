<?php
// src/User/DTO/UpdateCompanyDTO.php

namespace App\User\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateCompanyDTO
{
    #[Assert\Length(max: 255)]
    public ?string $name = null;

    #[Assert\Length(max: 500)]
    public ?string $full_name = null;

    #[Assert\Length(max: 500)]
    public ?string $legal_address = null;

    #[Assert\Length(max: 500)]
    public ?string $actual_address = null;

    #[Assert\Length(max: 255)]
    public ?string $ceo_fio = null;

    #[Assert\Length(max: 50)]
    public ?string $tax_system = null;

    #[Assert\PositiveOrZero]
    public ?int $employee_count = null;

    #[Assert\PositiveOrZero]
    public ?int $contract_count = null;

    public ?array $requisites = null;

    public ?array $management = null;

    public ?array $founders = null;

    public ?array $licenses = null;

    public ?array $contact_persons = null;

    public ?array $etp_accounts = null;

    #[Assert\Url]
    public ?string $webSite = null;

    #[Assert\Length(max: 50)]
    public ?string $officePhone = null;

    #[Assert\Length(max: 10)]
    public ?string $vatRate = null;
}
