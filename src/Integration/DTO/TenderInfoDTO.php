<?php
// src/Integration/DTO/TenderInfoDTO.php

namespace App\Integration\DTO;

// DTO для *ответа* нашему фронтенду
class TenderInfoDTO {
    public string $number;
    public string $law; // 44-ФЗ / 223-ФЗ
    public string $customer_name;
    public string $customer_inn;
    public float $initial_price; // НМЦК
    public float $security_amount; // Сумма обеспечения (БГ)
    public ?\DateTimeImmutable $end_date; // Дата окончания

    // --- "ЗАГЛУШКА" УБРАНА ---
    // Статический метод-фабрика, который парсит РЕАЛЬНЫЙ
    // ответ от API ЕИС (согласно вашему guide.txt)
    public static function fromEisResponse(array $data): self {
        $dto = new self();

        // (Используем ?? '', чтобы код не "упал", если поле пустое)
        $dto->number = $data['purchaseNumber'] ?? $data['regNumber'] ?? '';
        $dto->law = $data['law'] ?? ''; // (44FZ, 223FZ)

        // Вложенные данные
        $dto->customer_name = $data['customer']['organizationName'] ?? '';
        $dto->customer_inn = $data['customer']['inn'] ?? '';
        $dto->initial_price = (float)($data['priceInfo']['maxPrice'] ?? 0);

        // Самые вложенные данные
        $dto->security_amount = (float)($data['lots'][0]['customerRequirements']['contractGuarantee']['amount'] ?? 0);

        $endDateStr = $data['procedureInfo']['endDate'] ?? null;
        $dto->end_date = $endDateStr ? new \DateTimeImmutable($endDateStr) : null;

        return $dto;
    }
}
