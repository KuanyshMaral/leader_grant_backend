<?php
// src/Application/DTO/CalculatorRequestDTO.php

namespace App\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Этот DTO используется ТОЛЬКО для эндпоинта /api/applications/calculate.
 * Он идентичен CreateApplicationDTO, но НЕ содержит bank_ids.
 */
class CalculatorRequestDTO
{
    /**
     * @Assert\NotBlank(message="Тип продукта обязателен")
     * @Assert\Choice(
     * choices={"bank_guarantee", "credit", "tender_support", "factoring", "leasing", "insurance", "rko", "ved"},
     * message="Неизвестный тип продукта"
     * )
     */
    public string $product_type;

    /**
     * @Assert\NotBlank
     * @Assert\Positive(message="Сумма должна быть > 0")
     */
    public float $amount;

    /**
     * @Assert\NotBlank
     * @Assert\Positive(message="Срок должен быть > 0")
     */
    public int $term_days;

    /**
     * @Assert\NotBlank
     * @Assert\Valid // Валидировать вложенный ProductDataDTO
     */
    public ProductDataDTO $product_data;

    // ВАЖНО: client_user_id здесь не нужен,
    // так как мы возьмем ИНН из product_data,
    // а ID клиента (если нужно) из токена.
}
