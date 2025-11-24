<?php
// src/Bank/Api/BankController.php

namespace App\Bank\Api;

use App\Bank\DTO\BankDTO;
use App\Bank\Service\BankService;
use App\Bank\Service\BankCacheService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/banks')]
#[IsGranted('ROLE_ADMIN')]
class BankController extends AbstractController
{
    private const ADMIN_GROUPS = ['groups' => 'bank:admin:read'];

    public function __construct(
        private readonly BankService $bankService,
        private readonly BankCacheService $bankCacheService // ДОБАВЛЕНО: Cache Service
    ) {
    }

    /**
     * [Админ] Получает список всех банков с КЭШИРОВАНИЕМ.
     */
    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        // ОПТИМИЗИРОВАНО: Используем кэш вместо прямого запроса к БД
        $banks = $this->bankCacheService->getAllBanks();

        return $this->json($banks, 200, [], self::ADMIN_GROUPS);
    }

    /**
     * [Админ] Создает новый банк.
     */
    #[Route('', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] BankDTO $dto
    ): JsonResponse {

        $bank = $this->bankService->createBank($dto);

        return $this->json($bank, 201, [], self::ADMIN_GROUPS);
    }

    /**
     * [Админ] Получает один банк по ID.
     */
    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $bank = $this->bankService->getBank($id);

        return $this->json($bank, 200, [], self::ADMIN_GROUPS);
    }

    /**
     * [Админ] Обновляет банк.
     */
    #[Route('/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        #[MapRequestPayload] BankDTO $dto
    ): JsonResponse {

        $bank = $this->bankService->updateBank($id, $dto);

        return $this->json($bank, 200, [], self::ADMIN_GROUPS);
    }

    /**
     * [Админ] Обновляет только условия (тарифы) банка.
     */
    #[Route('/{id}/conditions', methods: ['PATCH'])]
    public function updateConditions(int $id, Request $request): JsonResponse
    {
        $conditions = json_decode($request->getContent(), true);

        $bank = $this->bankService->updateBankConditions($id, $conditions);

        return $this->json($bank, 200, [], self::ADMIN_GROUPS);
    }

    /**
     * [НОВЫЙ] Удалить банк (мягкое удаление).
     */
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->bankService->deleteBank($id);

        return $this->json(['message' => 'Bank archived successfully'], 200);
    }

    /**
     * [НОВЫЙ] Приостановить банк.
     */
    #[Route('/{id}/suspend', methods: ['PATCH'])]
    public function suspend(int $id): JsonResponse
    {
        $bank = $this->bankService->suspendBank($id);

        return $this->json($bank, 200, [], self::ADMIN_GROUPS);
    }

    /**
     * [НОВЫЙ] Активировать банк.
     */
    #[Route('/{id}/activate', methods: ['PATCH'])]
    public function activate(int $id): JsonResponse
    {
        $bank = $this->bankService->activateBank($id);

        return $this->json($bank, 200, [], self::ADMIN_GROUPS);
    }

    /**
     * [НОВЫЙ] Получить банки на аккредитации.
     */
    #[Route('/pending', methods: ['GET'])]
    public function pending(): JsonResponse
    {
        $banks = $this->bankService->getPendingAccreditation();

        return $this->json($banks, 200, [], self::ADMIN_GROUPS);
    }
}
