<?php
// src/Application/DTO/UpdateApplicationStatusDTO.php

namespace App\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateApplicationStatusDTO
{
    /**
     * @Assert\NotBlank(message="Новый статус не может быть пустым")
     * @Assert\Choice(
     * choices={"draft", "submitted", "bank_review", "pending_documents", "offer_received", "offer_accepted", "paid", "completed", "rejected", "archived"},
     * message="Недопустимое значение статуса"
     * )
     */
    public string $status;

    /**
     * (Опционально) JSON-поле для оферты, если статус = 'offer_received'
     * @Assert\Type("array")
     */
    public ?array $offer_data = null;

    /**
     * (Опционально) Причина, если статус = 'rejected'
     * @Assert\Type("string")
     */
    public ?string $rejection_reason = null;
}
