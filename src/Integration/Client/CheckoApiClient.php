<?php
// src/Integration/Client/CheckoApiClient.php

namespace App\Integration\Client;

use App\Integration\DTO\CompanyInfoDTO;
use App\Integration\Exception\ExternalApiConnectionException;
use App\Integration\Exception\ExternalApiDataNotFoundException;
use Symfony\Contracts\HttpClient\HttpClientInterface; // HTTP-клиент Symfony
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class CheckoApiClient
{
    private string $apiKey;
    private const BASE_URL = 'https://checko.ru/api/v1/company';

    // Внедряем HTTP-клиент, логгер и API-ключ (из .env)
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        string $checkoApiKey // Symfony внедрит это из .env
    ) {
        $this->apiKey = $checkoApiKey;
    }

    /**
     * @throws ExternalApiConnectionException
     * @throws ExternalApiDataNotFoundException
     */
    public function fetchCompanyInfo(string $inn): CompanyInfoDTO
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL, [
                'query' => [
                    'inn' => $inn,
                    'key' => $this->apiKey,
                ],
                'timeout' => 5.0, // Жесткий тайм-аут 5 секунд
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === Response::HTTP_NOT_FOUND) {
                $this->logger->info('API Checko: ИНН не найден', ['inn' => $inn]);
                throw new ExternalApiDataNotFoundException();
            }

            if ($statusCode !== Response::HTTP_OK) {
                throw new \Exception('API Error, status code: ' . $statusCode);
            }

            $data = $response->toArray(); // (JSON -> array)

            if (empty($data['data'])) {
                $this->logger->info('API Checko: ИНН найден, но данные пусты', ['inn' => $inn]);
                throw new ExternalApiDataNotFoundException();
            }

            // "Очищаем" грязный ответ в наш чистый DTO
            return CompanyInfoDTO::fromCheckoResponse($data['data']);

        } catch (\Exception $e) {
            $this->logger->error('Сбой API Checko', [
                'inn' => $inn,
                'error' => $e->getMessage()
            ]);

            if ($e instanceof ExternalApiDataNotFoundException) {
                throw $e; // Пробрасываем нашу ошибку "404"
            }

            // Все остальные ошибки - это 503 (Сервис недоступен)
            throw new ExternalApiConnectionException($e->getMessage());
        }
    }
}
