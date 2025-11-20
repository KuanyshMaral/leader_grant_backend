<?php
// src/User/Api/AgentController.php

namespace App\User\Api;

use App\User\Entity\User;
use App\User\Service\AgentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\User\DTO\AddClientDTO;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

#[Route('/api/agent')]
#[IsGranted('ROLE_AGENT')] // Только для Агентов
class AgentController extends AbstractController
{
    public function __construct(
        private readonly AgentService $agentService
    ) {}

    /**
     * Получить список моих клиентов.
     * GET /api/agent/clients
     */
    #[Route('/clients', methods: ['GET'])]
    public function listClients(#[CurrentUser] User $agent): JsonResponse
    {
        $clients = $this->agentService->getMyClients($agent);
        return $this->json($clients);
    }

    /**
     * Добавить клиента (привязка).
     */
    #[Route('/clients', methods: ['POST'])]
    public function addClient(
        #[CurrentUser] User $agent,
        #[MapRequestPayload] AddClientDTO $dto
    ): JsonResponse
    {
        $this->agentService->addClient($agent, $dto);
        return $this->json(['message' => 'Клиент успешно добавлен'], 201);
    }

    /**
     * Получить инфо о клиенте (Шапка).
     */
    #[Route('/clients/{id}', methods: ['GET'])]
    public function getClient(int $id, #[CurrentUser] User $agent): JsonResponse
    {
        try {
            $client = $this->agentService->getClient($agent, $id);
            return $this->json([
                'id' => $client->getId(),
                'fio' => $client->getFio(),
                'email' => $client->getEmail(),
                'company_name' => $client->getCompany()?->getName(),
                'inn' => $client->getCompany()?->getInn(),
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        }
    }

    /**
     * Получить документы клиента (Реализовано нормально).
     */
    #[Route('/clients/{id}/documents', methods: ['GET'])]
    public function getClientDocuments(int $id, #[CurrentUser] User $agent): JsonResponse
    {
        try {
            $documents = $this->agentService->getClientDocuments($agent, $id);
            return $this->json($documents, 200, [], ['groups' => 'doc:read']); // Используем группу сериализации
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        }
    }

    // --- СКЕЛЕТЫ ---

    /**
     * Получить заявки клиента (Скелет).
     */
    #[Route('/clients/{id}/applications', methods: ['GET'])]
    public function getClientApplications(int $id): JsonResponse
    {
        // Заглушка: возвращаем пустой массив, так как логика фильтрации сложная,
        // а нам нужен только UI.
        return $this->json([]);
    }

    /**
     * Получить данные компании клиента (Скелет).
     */
    #[Route('/clients/{id}/company', methods: ['GET'])]
    public function getClientCompany(int $id): JsonResponse
    {
        // Заглушка: возвращаем то, что есть в User.
        // В будущем здесь будет полная анкета.
        return $this->json(['status' => 'ok']);
    }
}
