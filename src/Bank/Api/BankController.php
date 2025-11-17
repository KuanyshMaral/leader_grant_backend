<?php
// src/Bank/Api/BankController.php

namespace App\Bank\Api;

use App\Bank\DTO\BankDTO;
use App\Bank\Service\BankService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// 1. Все роуты в этом файле начинаются с /api/admin/banks
// 2. [IsGranted] защищает ВЕСЬ контроллер.
//    Доступ только для пользователей с ROLE_ADMIN.
#[Route('/api/admin/banks')]
#[IsGranted('ROLE_ADMIN')]
class BankController extends AbstractController
{
    private const ADMIN_GROUPS = ['groups' => 'bank:admin:read'];

    public function __construct(
        private readonly BankService $bankService
    ) {
    }

    /**
     * [Админ] Получает список всех банков.
     */
    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $banks = $this->bankService->listAllBanks();

        // Используем группу 'bank:admin:read', чтобы вернуть 'conditions'
        return $this->json($banks, 200, [], self::ADMIN_GROUPS);
    }

    /**
     * [Админ] Создает новый банк.
     */
    #[Route('', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] BankDTO $dto // <-- Авто-валидация DTO
    ): JsonResponse {

        $bank = $this->bankService->createBank($dto);

        return $this->json($bank, 201, [], self::ADMIN_GROUPS); // 201 Created
    }

    /**
     * [Админ] Получает один банк по ID.
     */
    #[Route('/{id}', methods: ['GET'])]
    public function getOne(int $id): JsonResponse
    {
        // getBank() "выбросит" 404 Exception, если банк не найден
        $bank = $this->bankService->getBank($id);

        return $this->json($bank, 200, [], self::ADMIN_GROUPS);
    }

    /**
     * [Админ] Обновляет банк (полностью).
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
     * [Админ] Обновляет ТОЛЬКО тарифы (conditions) банка.
     */
    #[Route('/{id}/conditions', methods: ['PATCH'])]
    public function updateConditions(
        int $id,
        Request $request // <-- Мы не можем использовать DTO, т.к. нам нужен "сырой" JSON
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            return $this->json(['error' => 'Некорректный JSON'], 400);
        }

        $bank = $this->bankService->updateBankConditions($id, $data);

        return $this->json($bank, 200, [], self::ADMIN_GROUPS);
    }
}
