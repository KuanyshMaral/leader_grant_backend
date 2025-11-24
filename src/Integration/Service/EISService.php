<?php
// src/Integration/Service/EISService.php

namespace App\Integration\Service;

use App\Integration\DTO\ProcurementInfoDTO;
use Psr\Log\LoggerInterface;

/**
 * Mock сервис для интеграции с ЕИС (zakupki.gov.ru).
 * В будущем заменить на реальный API.
 */
class EISService
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}
    
    /**
     * Mock данные закупок.
     */
    private array $mockProcurements = [
        '0373100002421000123' => [
            'customer_name' => 'ФГУП ПОЧТА РОССИИ',
            'amount' => 5000000.00,
            'date_start' => '2024-01-15',
            'date_end' => '2024-12-31',
            'guarantee_type' => 'Обеспечение исполнения контракта'
        ],
        '0173200001520000456' => [
            'customer_name' => 'ГУП ВОДОКАНАЛ САНКТ-ПЕТЕРБУРГА',
            'amount' => 12500000.00,
            'date_start' => '2024-03-01',
            'date_end' => '2025-02-28',
            'guarantee_type' => 'Обеспечение заявки'
        ],
    ];

    /**
     * Получить данные закупки по номеру.
     */
    public function getProcurementByNumber(string $number): ProcurementInfoDTO
    {
        $this->logger->info('Searching procurement in EIS', ['number' => $number]);
        
        if (!isset($this->mockProcurements[$number])) {
            $this->logger->warning('Procurement not found in EIS', ['number' => $number]);
            throw new \RuntimeException("Закупка {$number} не найдена в ЕИС");
        }

        $data = $this->mockProcurements[$number];
        
        $this->logger->info('Procurement found in EIS', [
            'number' => $number,
            'customer' => $data['customer_name'],
            'amount' => $data['amount']
        ]);

        return new ProcurementInfoDTO(
            contract_number: $number,
            customer_name: $data['customer_name'],
            amount: $data['amount'],
            date_start: new \DateTimeImmutable($data['date_start']),
            date_end: new \DateTimeImmutable($data['date_end']),
            guarantee_type: $data['guarantee_type']
        );
    }
}
