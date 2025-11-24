<?php
// src/Integration/Service/EGRULService.php

namespace App\Integration\Service;

use App\Integration\DTO\CompanyInfoDTO;
use Psr\Log\LoggerInterface;

/**
 * Mock сервис для интеграции с ЕГРЮЛ.
 * В будущем заменить на реальный API (nalog.ru, dadata.ru).
 */
class EGRULService
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}
    
    /**
     * Mock данные компаний.
     */
    private array $mockCompanies = [
        '7707083893' => [
            'name' => 'ПАО СБЕРБАНК',
            'ogrn' => '1027700132195',
            'address' => 'г. Москва, ул. Вавилова, д. 19'
        ],
        '7736207543' => [
            'name' => 'АО АЛЬФА-БАНК',
            'ogrn' => '1027700067328',
            'address' => 'г. Москва, ул. Каланчевская, д. 27'
        ],
        '1234567890' => [
            'name' => 'ООО ТЕСТОВАЯ КОМПАНИЯ',
            'ogrn' => '1234567890123',
            'address' => 'г. Казань, ул. Баумана, д. 1'
        ],
    ];

    /**
     * Поиск компании по ИНН.
     */
    public function getCompanyByINN(string $inn): CompanyInfoDTO
    {
        $this->logger->info('Searching company by INN', ['inn' => $inn]);
        
        if (!isset($this->mockCompanies[$inn])) {
            $this->logger->warning('Company not found in EGRUL', ['inn' => $inn]);
            throw new \RuntimeException("Компания с ИНН {$inn} не найдена в ЕГРЮЛ");
        }

        $data = $this->mockCompanies[$inn];
        
        $this->logger->info('Company found in EGRUL', [
            'inn' => $inn,
            'name' => $data['name']
        ]);

        return new CompanyInfoDTO(
            inn: $inn,
            name: $data['name'],
            ogrn: $data['ogrn'],
            address: $data['address'],
            status: 'active'
        );
    }
}
