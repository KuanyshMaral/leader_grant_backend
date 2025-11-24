<?php
// src/Agent/Api/AgentContractController.php

namespace App\Agent\Api;

use App\Agent\Service\AgentContractService;
use App\User\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/agent/contract', name: 'agent_contract_')]
#[IsGranted('ROLE_AGENT')]
class AgentContractController extends AbstractController
{
    public function __construct(
        private readonly AgentContractService $contractService
    ) {
    }

    /**
     * Получить условия агентского вознаграждения.
     * 
     * GET /api/agent/contract/rewards
     */
    #[Route('/rewards', name: 'rewards', methods: ['GET'])]
    public function getRewards(#[CurrentUser] User $agent): JsonResponse
    {
        $rewards = $this->contractService->getAgentRewards($agent);
        
        return new JsonResponse($rewards);
    }

    /**
     * Получить документы агентского договора.
     * 
     * GET /api/agent/contract/documents
     */
    #[Route('/documents', name: 'documents', methods: ['GET'])]
    public function getDocuments(#[CurrentUser] User $agent): JsonResponse
    {
        $documents = $this->contractService->getContractDocuments($agent);
        
        return new JsonResponse($documents);
    }
}
