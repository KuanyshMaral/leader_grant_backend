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
    public \DateTime $end_date; // Дата окончания

    // Статический метод-фабрика для "очистки" ответа от API ЕИС (zakupki.gov.ru)
    public static function fromEisResponse(array $dirtyData): self {
        $dto = new self();
        // Здесь будет логика парсинга сложного XML/JSON ответа от ЕИС
        // (Это примерные поля, в реальности структура ответа ЕИС сложнее)
        $dto->number = $dirtyData['commonInfo']['purchaseNumber'] ?? '';
        $dto->law = $dirtyData['commonInfo']['placingWay']['name'] ?? ''; // (Надо будет парсить)
        $dto->customer_name = $dirtyData['customer']['fullName'] ?? '';
        $dto->customer_inn = $dirtyData['customer']['inn'] ?? '';
        $dto->initial_price = (float)($dirtyData['lot']['maxPrice'] ?? 0);
        $dto->security_amount = (float)($dirtyData['lot']['contractGuarantee']['amount'] ?? 0);
        $dto->end_date = new \DateTime($dirtyData['commonInfo']['endDate'] ?? 'now');

        return $dto;
    }
}