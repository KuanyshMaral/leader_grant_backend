<?php
// src/Agent/Api/AgentCommissionController.php

namespace App\Agent\Api;

use App\Agent\Service\AgentCommissionService;
use App\User\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/agent/commissions')]
#[IsGranted('ROLE_AGENT')]
class AgentCommissionController extends AbstractController
{
    public function __construct(
        private readonly AgentCommissionService $commissionService
    ) {
    }

    /**
     * Получить список комиссий агента.
     * GET /api/agent/commissions
     */
    #[Route('', methods: ['GET'])]
    public function listMyCommissions(#[CurrentUser] User $agent): JsonResponse
    {
        try {
            $commissions = $this->commissionService->getAgentCommissions($agent);
            
            return $this->json($commissions, 200, [], ['groups' => 'commission:read']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
