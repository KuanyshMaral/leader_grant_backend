<?php
// src/Application/DTO/ProductDataDTO.php

namespace App\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

// DTO объединяет все поля из CSV-файлов (СЗ‑Банковская гарантия, СЗ‑Лизинг и т.д.)
class ProductDataDTO {

    // --- ОБЩЕЕ ПОЛЕ (есть почти везде) ---
    /**
     * @Assert\NotBlank(message="ИНН заявителя обязателен")
     * @Assert\Length(min=10, max=12)
     */
    public string $client_inn;

    // --- Поля для БГ и Кредита на исполнение ---
    public ?string $procurement_number = null; // № закупки
    public ?string $customer_inn = null;       // ИНН Заказчика
    public ?string $law = null;                // (Enum: 44-ФЗ, 223-ФЗ, 185-ФЗ, КБГ)
    public ?string $bg_type = null;            // (Enum: Обеспечения заявки, ...)
    public ?bool $has_advance = false;          // Наличие аванса (галочка)
    public ?bool $is_closed_tender = false;     // Закрытый конкурс (галочка)

    // --- Дополнительные поля для БГ ---
    public ?string $guarantee_type = null; // enum: заявка, исполнение, возврат_аванса
    public bool $is_re_guarantee = false; // переобеспечение
    public bool $is_single_supplier = false; // единственный поставщик
    public ?string $customer_name = null; // название заказчика

    // --- Старые совместимые поля ---
    public bool $is_resecurity = false;        // Является переобеспечением (оставлено для совместимости)
    public bool $sole_supplier = false;        // Единственный поставщик (оставлено для совместимости)
    public bool $without_eis = false;          // Без размещения в ЕИС

    // --- Поля для Корп. кредита / Лизинга ---
    public ?string $credit_type = null;        // (Enum: Разовый кредит, Возобновляемая линия, ...)

    // --- Поля для Факторинга ---
    public ?string $contractor_inn = null;     // ИНН контрагента
    /**
     * @Assert\Choice(choices={"classical", "closed", "purchasing"}, message="Неверный тип факторинга")
     */
    public ?string $factoring_type = null;

    // --- СТРАХОВАНИЕ ---
    /**
     * @Assert\Choice(choices={"personnel", "transport", "property", "liability"}, message="Неверный вид страхования")
     */
    public ?string $insurance_type = null;

    // --- ТЕНДЕРНОЕ СОПРОВОЖДЕНИЕ ---
    /**
     * @Assert\Choice(choices={"one_time", "turnkey"}, message="Неверный вариант сопровождения")
     */
    public ?string $support_option = null;
    public ?string $industry = null; // Отрасль

    // --- ВЭД ---
    public ?string $currency = null;
    public ?string $country = null;

    // --- БГ (Кросс‑продажа) ---
    public bool $need_credit = false; // Галочка "Клиенту нужен кредит"
}
