<?php
// src\Agent\Api\AdminCommissionController.php

namespace App\Agent\Api;

use App\Agent\Service\AgentCommissionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/commissions')]
#[IsGranted('ROLE_ADMIN')]
class AdminCommissionController extends AbstractController
{
    public function __construct(
        private readonly AgentCommissionService $commissionService
    ) {
    }

    /**
     * Получить все комиссии (для админа).
     * GET /api/admin/commissions
     */
    #[Route('', methods: ['GET'])]
    public function listAllCommissions(): JsonResponse
    {
        try {
            $commissions = $this->commissionService->getAllCommissions();
            
            return $this->json($commissions, 200, [], ['groups' => 'commission:read']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Отметить комиссию как выплаченную.
     * PATCH /api/admin/commissions/{id}/pay
     */
    #[Route('/{id}/pay', methods: ['PATCH'])]
    public function markAsPaid(int $id): JsonResponse
    {
        try {
            $commission = $this->commissionService->markAsPaid($id);
            
            return $this->json([
                'message' => 'Комиссия отмечена как выплаченная',
                'commission_id' => $commission->getId(),
                'status' => $commission->getStatus()
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
