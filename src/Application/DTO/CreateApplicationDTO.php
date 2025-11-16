<?php
// src/Application/DTO/CreateApplicationDTO.php

namespace App\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateApplicationDTO {
    /**
     * @Assert\NotBlank(message="ID клиента обязателен")
     * @Assert\Positive
     */
    public int $client_user_id;

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
    public int $term_days; // Срок в днях

    /**
     * @Assert\NotBlank(message="Нужно выбрать хотя бы один банк")
     * @Assert\Count(min=1, minMessage="Нужно выбрать хотя бы один банк")
     * @Assert\All({
     * @Assert\Type("integer")
     * })
     */
    public array $bank_ids; // Массив ID банков [1, 5, 8]

    /**
     * @Assert\NotBlank
     * @Assert\Valid // <-- Эта строка "проваливается" в ProductDataDTO и валидирует его
     */
    public ProductDataDTO $product_data;

    // Конструктор для удобного создания из Контроллера
    // (В Laravel/Symfony фреймворк часто делает это сам)
    public function __construct(array $data) {
        $this->client_user_id = $data['client_user_id'];
        $this->product_type = $data['product_type'];
        $this->amount = (float)$data['amount'];
        $this->term_days = (int)$data['term_days'];
        $this->bank_ids = $data['bank_ids'];

        // Вложенный DTO создается из "сырого" массива product_data
        $this->product_data = new ProductDataDTO($data['product_data'] ?? []);
    }
}