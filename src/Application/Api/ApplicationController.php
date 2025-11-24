<?php
// src/Application/Api/ApplicationController.php

namespace App\Application\Api;

use App\Application\DTO\CalculatorRequestDTO;
use App\Application\DTO\CreateApplicationDTO;
use App\Application\DTO\UpdateApplicationStatusDTO;
use App\Application\Service\ApplicationService;
use App\Application\Service\CalculatorService;
use App\Shared\Api\BaseController;
use App\Shared\DTO\PaginationRequestDTO;
use App\User\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

// Все роуты здесь будут требовать JWT-токен (согласно security.yaml)
#[Route('/api/applications')]
class ApplicationController extends BaseController
{
    public function __construct(
        LoggerInterface $logger,
        private readonly ApplicationService $applicationService,
        private readonly CalculatorService $calculatorService
    ) {
        parent::__construct($logger);
    }

    /**
     * Эндпоинт "Калькулятора" (Шаг 1).
     */
    #[Route('/calculate/{type}', methods: ['POST'])]
    public function calculate(
        string $type,
        #[MapRequestPayload] CalculatorRequestDTO $dto,
        Request $request
    ): JsonResponse {
        $endpoint = "POST /api/applications/calculate/{$type}";
        $this->logRequest($request, $endpoint);

        try {
            $dto->product_type = $type;
            $result = $this->calculatorService->calculate($dto);
            
            $this->logResponse($endpoint, 200, [
                'product_type' => $type,
                'amount' => $dto->amount ?? null
            ]);
            
            return new JsonResponse($result);
        } catch (\Exception $e) {
            $this->logApiError($endpoint, $e, ['product_type' => $type]);
            throw $e;
        }
    }

    /**
     * Эндпоинт "Создание Заявки" (Шаг 2).
     */
    #[Route('', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateApplicationDTO $dto,
        #[CurrentUser] User $creator,
        Request $request
    ): JsonResponse {
        $endpoint = 'POST /api/applications';
        $this->logRequest($request, $endpoint);

        try {
            $applications = $this->applicationService->createApplications($dto, $creator);
            $createdIds = array_map(fn($app) => $app->getId(), $applications);

            $this->logResponse($endpoint, 201, [
                'created_count' => count($applications),
                'created_ids' => $createdIds
            ]);

            return new JsonResponse([
                'message' => 'Заявки успешно созданы',
                'created_ids' => $createdIds
            ], 201);
        } catch (\Exception $e) {
            $this->logApiError($endpoint, $e);
            throw $e;
        }
    }

    /**
     * Эндпоинт "Список Заявок" (с пагинацией и фильтрацией).
     */
    #[Route('', methods: ['GET'])]
    public function list(
        #[CurrentUser] User $user,
        #[MapQueryString] ?PaginationRequestDTO $pagination,
        Request $request
    ): JsonResponse {
        $endpoint = 'GET /api/applications';
        $this->logRequest($request, $endpoint);

        try {
            $pagination ??= new PaginationRequestDTO();

            $filters = [
                'status' => $request->query->get('status'),
                'product' => $request->query->get('product'),
                'bank_id' => $request->query->get('bank_id'),
                'agent_id' => $request->query->get('agent_id'),
                'client_id' => $request->query->get('client_id'),
                'date_from' => $request->query->get('date_from'),
                'date_to' => $request->query->get('date_to'),
                'amount_min' => $request->query->get('amount_min'),
                'amount_max' => $request->query->get('amount_max'),
                'search' => $request->query->get('search'),
            ];

            $result = $this->applicationService->listForUser($user, $pagination, $filters);

            $this->logResponse($endpoint, 200, [
                'total' => $result['total'] ?? 0,
                'page' => $result['page'] ?? 1
            ]);

            // ВАЖНО: возвращаем с serialization groups
            return $this->json($result, 200, [], ['groups' => 'app:read']);
        } catch (\Exception $e) {
            $this->logApiError($endpoint, $e);
            throw $e;
        }
    }

    /**
     * Эндпоинт "Одна Заявка" (с проверкой прав).
     */
    #[Route('/{id}', methods: ['GET'])]
    public function getOne(
        int $id,
        #[CurrentUser] User $user,
        Request $request
    ): JsonResponse {
        $endpoint = "GET /api/applications/{$id}";
        $this->logRequest($request, $endpoint);

        try {
            $application = $this->applicationService->getApplicationForUser($id, $user);

            $this->logResponse($endpoint, 200, ['application_id' => $id]);

            return $this->json($application, 200, [], ['groups' => 'app:read']);
        } catch (\Exception $e) {
            $this->logApiError($endpoint, $e, ['application_id' => $id]);
            throw $e;
        }
    }

    /**
     * Эндпоинт "Смена Статуса" (для Админа/Партнера).
     */
    #[Route('/{id}/status', methods: ['PATCH'])]
    public function updateStatus(
        int $id,
        #[MapRequestPayload] UpdateApplicationStatusDTO $dto,
        #[CurrentUser] User $updater,
        Request $request
    ): JsonResponse {
        $endpoint = "PATCH /api/applications/{$id}/status";
        $this->logRequest($request, $endpoint);

        try {
            $application = $this->applicationService->updateStatus($id, $dto, $updater);

            $this->logResponse($endpoint, 200, [
                'application_id' => $id,
                'new_status' => $dto->status
            ]);

            return new JsonResponse($application, 200, [], ['groups' => 'app:read']);
        } catch (\Exception $e) {
            $this->logApiError($endpoint, $e, [
                'application_id' => $id,
                'status' => $dto->status
            ]);
            throw $e;
        }
    }

    /**
     * Получить историю изменений статуса заявки.
     */
    #[Route('/{id}/status-history', methods: ['GET'])]
    public function getStatusHistory(
        int $id,
        #[CurrentUser] User $user,
        Request $request,
        \App\Application\Repository\ApplicationStatusHistoryRepository $historyRepository
    ): JsonResponse {
        $endpoint = "GET /api/applications/{$id}/status-history";
        $this->logRequest($request, $endpoint);

        try {
            $application = $this->applicationService->getApplicationForUser($id, $user);
            $history = $historyRepository->findByApplication($application);

            $this->logResponse($endpoint, 200, [
                'application_id' => $id,
                'history_count' => count($history)
            ]);

            return new JsonResponse($history, 200, [], ['groups' => 'history:read']);
        } catch (\Exception $e) {
            $this->logApiError($endpoint, $e, ['application_id' => $id]);
            throw $e;
        }
    }

    /**
     * Получить документы заявки.
     */
    #[Route('/{id}/documents', methods: ['GET'])]
    public function getDocuments(
        int $id,
        #[CurrentUser] User $user,
        Request $request,
        \App\Document\Repository\DocumentRepository $documentRepository
    ): JsonResponse {
        $endpoint = "GET /api/applications/{$id}/documents";
        $this->logRequest($request, $endpoint);

        try {
            $application = $this->applicationService->getApplicationForUser($id, $user);
            $documents = $documentRepository->findByApplication($application);

            $this->logResponse($endpoint, 200, [
                'application_id' => $id,
                'documents_count' => count($documents)
            ]);

            return $this->json($documents, 200, [], ['groups' => 'doc:read']);
        } catch (\Exception $e) {
            $this->logApiError($endpoint, $e, ['application_id' => $id]);
            throw $e;
        }
    }
}
