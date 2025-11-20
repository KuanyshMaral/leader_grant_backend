<?php
// src/Application/Api/ApplicationController.php

namespace App\Application\Api;

use App\Application\DTO\CalculatorRequestDTO;
use App\Application\DTO\CreateApplicationDTO;
use App\Application\DTO\UpdateApplicationStatusDTO;
use App\Application\Service\ApplicationService;
use App\Application\Service\CalculatorService;
use App\Shared\DTO\PaginationRequestDTO;
use App\User\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request; // <-- ДОБАВЛЕНО
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

// Все роуты здесь будут требовать JWT-токен (согласно security.yaml)
#[Route('/api/applications')]
class ApplicationController extends AbstractController
{
    /**
     * Эндпоинт "Калькулятора" (Шаг 1).
     */
    #[Route('/calculate', methods: ['POST'])]
    public function calculate(
        #[MapRequestPayload] CalculatorRequestDTO $dto,
        CalculatorService $calculatorService
    ): JsonResponse {

        $result = $calculatorService->calculate($dto);

        return $this->json($result);
    }

    /**
     * Эндпоинт "Создание Заявки" (Шаг 2).
     */
    #[Route('', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateApplicationDTO $dto,
        #[CurrentUser] User $creator,
        ApplicationService $applicationService
    ): JsonResponse {

        $applications = $applicationService->createApplications($dto, $creator);

        return $this->json([
            'message' => 'Заявки успешно созданы',
            'created_ids' => array_map(fn($app) => $app->getId(), $applications)
        ], 201);
    }

    /**
     * Эндпоинт "Список Заявок" (с пагинацией и фильтрацией).
     * Пример: GET /api/applications?page=1&status=rejected&product=bank_guarantee
     */
    #[Route('', methods: ['GET'])]
    public function list(
        #[CurrentUser] User $user,
        #[MapQueryString] ?PaginationRequestDTO $pagination,
        Request $request, // <-- Читаем параметры запроса
        ApplicationService $applicationService
    ): JsonResponse {

        $pagination ??= new PaginationRequestDTO();

        // Читаем фильтры из URL
        $status = $request->query->get('status');
        $product = $request->query->get('product');

        $result = $applicationService->listForUser($user, $pagination, $status, $product);

        return $this->json($result, 200, [], ['groups' => 'app:read']);
    }

    /**
     * Эндпоинт "Одна Заявка" (с проверкой прав).
     */
    #[Route('/{id}', methods: ['GET'])]
    public function getOne(
        int $id,
        #[CurrentUser] User $user,
        ApplicationService $applicationService
    ): JsonResponse {

        $application = $applicationService->getApplicationForUser($id, $user);

        return $this->json($application, 200, [], ['groups' => 'app:read']);
    }

    /**
     * Эндпоинт "Смена Статуса" (для Админа/Партнера).
     */
    #[Route('/{id}/status', methods: ['PATCH'])]
    public function updateStatus(
        int $id,
        #[MapRequestPayload] UpdateApplicationStatusDTO $dto,
        #[CurrentUser] User $updater,
        ApplicationService $applicationService
    ): JsonResponse {

        $application = $applicationService->updateStatus($id, $dto, $updater);

        return $this->json($application, 200, [], ['groups' => 'app:read']);
    }
}
