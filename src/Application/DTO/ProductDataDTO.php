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
    public ?bool $has_advance;          // Наличие аванса (галочка)
    public ?bool $is_closed_tender;     // Закрытый конкурс (галочка)

    // --- Поля для Корп. кредита / Лизинга ---
    public ?string $credit_type;        // (Enum: Разовый кредит, Возобновляемая линия, ...)

    // --- Поля для Факторинга ---
    public ?string $contractor_inn;     // ИНН контрагента
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

    // Конструктор, который безопасно "разбирает" массив
    public function __construct(array $data) {
        $this->client_inn = $data['client_inn'] ?? '';

        // БГ и Кредит
        $this->procurement_number = $data['procurement_number'] ?? null;
        $this->customer_inn = $data['customer_inn'] ?? null;
        $this->law = $data['law'] ?? null;
        $this->bg_type = $data['bg_type'] ?? null;
        $this->has_advance = $data['has_advance'] ?? null;
        $this->is_closed_tender = $data['is_closed_tender'] ?? null;

        // Кредит / Лизинг
        $this->credit_type = $data['credit_type'] ?? null;

        // Факторинг
        $this->contractor_inn = $data['contractor_inn'] ?? null;
        $this->factoring_type = $data['factoring_type'] ?? null;

        // ВЭД
        $this->currency = $data['currency'] ?? null;
        $this->country = $data['country'] ?? null;

        // Страхование
        $this->insurance_type = $data['insurance_type'] ?? null;
        $this->insurance_product = $data['insurance_product'] ?? null;

        // Сопровождение
        $this->support_option = $data['support_option'] ?? null;
        $this.industry = $data['industry'] ?? null;
    }
}