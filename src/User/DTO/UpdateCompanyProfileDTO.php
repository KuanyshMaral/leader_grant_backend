<?php
// src/User/DTO/UpdateCompanyProfileDTO.php

namespace App\User\DTO;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateCompanyProfileDTO {
    /** @Assert\NotBlank */
    public string $inn;

    /** @Assert\NotBlank */
    public string $ogrn;

    public ?string $kpp = null;

    /** @Assert\NotBlank */
    public string $name;

    /** @Assert\NotBlank */
    public string $full_name;

    /** @Assert\NotBlank */
    public string $legal_address;

    public ?string $actual_address = null;

    /** @Assert\NotBlank */
    public string $tax_system;

    // Регистрационные данные
    public ?string $okpo = null;
    public ?string $oktmo = null;
    public ?string $okved = null;
    public ?string $registration_date = null; // Приходит как строка "YYYY-MM-DD"
    public ?string $authorized_capital = null;
    public ?string $paid_capital = null;

    public ?int $employee_count = null;
    public ?int $contract_count = null;

    /**
     * @Assert\Type("array")
     */
    public array $requisites = []; // Теперь это просто массив, так гибче

    /** @Assert\Type("array") */
    public ?array $management = [];

    /** @Assert\Type("array") */
    public ?array $founders = [];

    /** @Assert\Type("array") */
    public ?array $licenses = [];

    /** @Assert\Type("array") */
    public ?array $contact_persons = [];

    /** @Assert\Type("array") */
    public ?array $etp_accounts = [];
}
