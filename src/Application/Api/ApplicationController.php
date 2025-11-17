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
     * Принимает DTO, возвращает список одобренных/отклоненных банков.
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
     * Принимает DTO (уже с bank_ids) и создает заявки.
     */
    #[Route('', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateApplicationDTO $dto,
        #[CurrentUser] User $creator, // Тот, кто нажал кнопку (Клиент или Агент)
        ApplicationService $applicationService
    ): JsonResponse {

        // Вся логика (поиск клиента, создание заявок, "крик" в очередь)
        // спрятана внутри ApplicationService.
        $applications = $applicationService->createApplications($dto, $creator);

        return $this->json([
            'message' => 'Заявки успешно созданы',
            'created_ids' => array_map(fn($app) => $app->getId(), $applications)
        ], 201); // 201 Created
    }

    /**
     * Эндпоинт "Список Заявок" (с пагинацией).
     * Автоматически фильтрует по роли (Клиент/Агент/Партнер/Админ).
     */
    #[Route('', methods: ['GET'])]
    public function list(
        #[CurrentUser] User $user,
        #[MapQueryString] ?PaginationRequestDTO $pagination, // Валидация ?page=1&limit=20
        ApplicationService $applicationService
    ): JsonResponse {

        $pagination ??= new PaginationRequestDTO(); // (Если ?page не указан)

        $result = $applicationService->listForUser($user, $pagination);

        // Мы используем 'groups' => 'app:read', чтобы JSON был "чистым"
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

        // Вся логика (включая проверку "может ли $user видеть заявку $id")
        // спрятана внутри getApplicationForUser.
        // Если прав нет, он "выбросит" 403 Exception.
        $application = $applicationService->getApplicationForUser($id, $user);

        return $this->json($application, 200, [], ['groups' => 'app:read']);
    }

    /**
     * Эндпоинт "Смена Статуса" (для Админа/Партнера).
     */
    #[Route('/{id}/status', methods: ['PATCH'])] // PATCH - частичное обновление
    public function updateStatus(
        int $id,
        #[MapRequestPayload] UpdateApplicationStatusDTO $dto, // Валидация DTO
        #[CurrentUser] User $updater, // Админ или Партнер
        ApplicationService $applicationService
    ): JsonResponse {

        // Вся логика (проверка прав, смена статуса, сохранение оферты)
        // спрятана внутри.
        $application = $applicationService->updateStatus($id, $dto, $updater);

        return $this->json($application, 200, [], ['groups' => 'app:read']);
    }
}
