<?php
// src/Integration/Client/TenderApiClient.php

namespace App\Integration\Client;

use App\Integration\DTO\TenderInfoDTO;
use App\Integration\Exception\ExternalApiConnectionException;
use App\Integration\Exception\ExternalApiDataNotFoundException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class TenderApiClient
{
    // --- "ЗАГЛУШКА" УБРАНА ---
    // Это реальный URL из вашего guide.txt
    private const BASE_URL = 'https://api.zakupki.gov.ru/epz/order/extendedsearch/results.json';
    private string $apiKey;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        string $eisApiKey // <-- 1. Мы будем "просить" ключ из .env
    ) {
        $this->apiKey = $eisApiKey;
    }

    /**
     * @throws ExternalApiConnectionException
     * @throws ExternalApiDataNotFoundException
     */
    public function fetchTenderInfo(string $procurementNumber): TenderInfoDTO
    {
        // --- TODO (ЗАГЛУШКА API-КЛЮЧА) ---
        // Этот код ПОЛНОСТЬЮ РАБОЧИЙ, но он "упадет" с ошибкой 401
        // (Unauthorized), пока в .env не будет вставлен
        // РЕАЛЬНЫЙ токен от zakupki.gov.ru.
        if (empty($this->apiKey)) {
            $this->logger->error('API ЕИС не настроен: $eisApiKey пуст. Проверьте .env');
            throw new ExternalApiConnectionException('API ЕИС (Закупки) не настроен.');
        }
        // --- КОНЕЦ TODO ---

        try {
            $response = $this->httpClient->request('GET', self::BASE_URL, [
                'headers' => [
                    // 2. Добавляем токен в заголовки (как требует guide.txt)
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'query' => [
                    // 3. Используем параметры запроса из guide.txt
                    'searchString' => $procurementNumber,
                    'recordsPerPage' => 1,
                    'pageNumber' => 1,
                ],
                'timeout' => 5.0,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === Response::HTTP_NOT_FOUND) {
                throw new ExternalApiDataNotFoundException();
            }
            if ($statusCode === Response::HTTP_UNAUTHORIZED) {
                throw new ExternalApiConnectionException('API ЕИС (Закупки): Неверный API-ключ (Токен).');
            }
            if ($statusCode !== Response::HTTP_OK) {
                throw new \Exception('API Error, status code: ' . $statusCode);
            }

            // 4. "toArray()" - это ПРАВИЛЬНО (согласно guide.txt)
            $data = $response->toArray();

            if (empty($data['data']) || $data['total'] === 0) {
                $this->logger->info('API ЕИС: Закупка найдена, но данные пусты', ['number' => $procurementNumber]);
                throw new ExternalApiDataNotFoundException();
            }

            // 5. "Очищаем" грязный ответ (первый элемент) в наш чистый DTO
            return TenderInfoDTO::fromEisResponse($data['data'][0]);

        } catch (\Exception $e) {
            $this->logger->error('Сбой API ЕИС (Закупки)', [
                'number' => $procurementNumber,
                'error' => $e->getMessage()
            ]);

            if ($e instanceof (ExternalApiDataNotFoundException::class) || $e instanceof (ExternalApiConnectionException::class)) {
                throw $e;
            }

            throw new ExternalApiConnectionException($e->getMessage());
        }
    }
}
