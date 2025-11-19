<?php
// src/Application/DTO/ProductDataDTO.php

namespace App\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

// Это DTO "объединяет" все поля из CSV-файлов (СЗ-Банковская гарантия, СЗ-Лизинг и т.д.)
class ProductDataDTO {

    // --- ОБЩЕЕ ПОЛЕ (есть почти везде) ---
    /**
     * @Assert\NotBlank(message="ИНН заявителя обязателен")
     * @Assert\Length(min=10, max=12)
     */
    public string $client_inn;

    // --- Поля для БГ и Кредита на исполнение ---
    public ?string $procurement_number; // № закупки
    public ?string $customer_inn;       // ИНН Заказчика
    public ?string $law;                // (Enum: 44-ФЗ, 223-ФЗ, 185-ФЗ, КБГ)
    public ?string $bg_type;            // (Enum: Обеспечения заявки, ...)
    public ?bool $has_advance = false;          // Наличие аванса (галочка)
    public ?bool $is_closed_tender = false;     // Закрытый конкурс (галочка)

    public bool $is_resecurity = false;        // Является переобеспечением
    public bool $sole_supplier = false;        // Единственный поставщик
    public bool $without_eis = false;          // Без размещения в ЕИС

    // --- Поля для Корп. кредита / Лизинга ---
    public ?string $credit_type;        // (Enum: Разовый кредит, Возобновляемая линия, ...)

    // --- Поля для Факторинга ---
    public ?string $contractor_inn = null;     // ИНН контрагента
    public ?string $factoring_type;     // (Enum: Классический, Закрытый, ...)

    // --- Поля для ВЭД ---
    public ?string $currency;           // Валюта
    public ?string $country;            // Страна платежа

    // --- Поля для Страхования ---
    public ?string $insurance_type;     // (Enum: Персонал, Транспорт, Имущество, ...)
    public ?string $insurance_product;  // (Enum: ДМС, ОСАГО, ...)

    // --- Поля для Тендерного сопровождения ---
    public ?string $support_option;     // (Enum: Разовое, Под ключ)
    public ?string $industry;           // Отрасль закупок


}
