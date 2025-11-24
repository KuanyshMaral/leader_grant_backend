<?php
// src/Integration/DTO/ProcurementInfoDTO.php

namespace App\Integration\DTO;

/**
 * DTO для информации о закупке из ЕИС.
 */
class ProcurementInfoDTO
{
    public function __construct(
        public readonly string $contract_number,
        public readonly string $customer_name,
        public readonly float $amount,
        public readonly \DateTimeImmutable $date_start,
        public readonly \DateTimeImmutable $date_end,
        public readonly ?string $guarantee_type = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'contract_number' => $this->contract_number,
            'customer_name' => $this->customer_name,
            'amount' => $this->amount,
            'date_start' => $this->date_start->format('Y-m-d'),
            'date_end' => $this->date_end->format('Y-m-d'),
            'guarantee_type' => $this->guarantee_type
        ];
    }
}
