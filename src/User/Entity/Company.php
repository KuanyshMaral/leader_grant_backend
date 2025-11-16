<?php
class Company {
    private int $id;

    // Связь с владельцем
    // One-to-One: Одна Company у одного User
    private User $user;            // (ForeignKey -> users.id)

    // Данные из API (Checko)
    private string $name;          // Краткое имя
    private string $full_name;
    private string $inn;           // (Unique)
    private string $ogrn;
    private string $legal_address; // Юр. адрес
    private string $ceo_fio;       // ФИО Руководителя

    // Реквизиты
    private string $tax_system;    // (Enum: 'OSN', 'USN')
    private array $requisites;      // (jsonb: БИК, Р/С, Кор.счет)

    private \DateTime $created_at;
}?>