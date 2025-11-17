<?php
// src/Integration/Service/IntegrationService.php

namespace App\Integration\Service;

use App\Integration\Client\CheckoApiClient;
use App\Integration\Client\TenderApiClient;
use App\Integration\DTO\CompanyInfoDTO;
use App\Integration\DTO\TenderInfoDTO;
use App\Integration\Exception\ExternalApiConnectionException;
use App\Integration\Exception\ExternalApiDataNotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Сервис-прокси для всех внешних интеграций.
 * Он координирует API-клиенты и отдает чистые DTO.
 */
class IntegrationService
{
    public function __construct(
        private readonly CheckoApiClient $checkoClient,
        private readonly TenderApiClient $tenderClient,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Получает информацию о компании по ИНН.
     * Используется для автозаполнения форм.
     *
     * @throws ExternalApiDataNotFoundException (Если 404)
     * @throws ExternalApiConnectionException (Если 500/Таймаут)
     */
    public function getCompanyInfoByInn(string $inn): CompanyInfoDTO
    {
        $this->logger->info('Запрос информации по ИНН', ['inn' => $inn]);

        // Вся сложная логика (HTTP, таймауты, парсинг)
        // спрятана внутри CheckoApiClient.
        return $this->checkoClient->fetchCompanyInfo($inn);
    }

    /**
     * Получает информацию о тендере по номеру закупки.
     * Используется для автозаполнения калькулятора.
     *
     * @throws ExternalApiDataNotFoundException (Если 404)
     * @throws ExternalApiConnectionException (Если 500/Таймаут)
     */
    public function getTenderInfoByNumber(string $procurementNumber): TenderInfoDTO
    {
        $this->logger->info('Запрос информации по Закупке', ['number' => $procurementNumber]);

        // Вся логика спрятана внутри TenderApiClient.
        return $this->tenderClient->fetchTenderInfo($procurementNumber);
    }
}
