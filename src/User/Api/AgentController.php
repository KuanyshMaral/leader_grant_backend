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

    /**
     * Загрузить документ для клиента.
     */
    #[Route('/clients/{id}/documents', methods: ['POST'])]
    public function uploadClientDocument(
        int $id,
        \Symfony\Component\HttpFoundation\Request $request,
        #[CurrentUser] User $agent
    ): JsonResponse {
        $file = $request->files->get('file');
        $docType = $request->request->get('docType');

        if (!$file || !$docType) {
            return $this->json(['error' => 'File and docType are required'], 400);
        }

        try {
            $document = $this->agentService->uploadClientDocument($agent, $id, $file, $docType);
            return $this->json([
                'id' => $document->getId(),
                'status' => 'uploaded'
            ], 201);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
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
        return $this->json(['status' => 'ok']);
    }

    #[Route('/clients/{id}/interactions', methods: ['GET'])]
    public function getClientInteractions(int $id, #[CurrentUser] User $agent, \App\Agent\Repository\AgentClientInteractionRepository $repo): JsonResponse
    {
        $client = $this->userRepository->find($id);
        if (!$client || $client->getReferrerAgent()?->getId() !== $agent->getId()) return $this->json(['error' => 'Клиент не найден'], 404);
        return $this->json($repo->findByAgentAndClient($agent, $client), 200, [], ['groups' => 'interaction:read']);
    }

    #[Route('/clients/{id}/interactions', methods: ['POST'])]
    public function addClientInteraction(int $id, #[MapRequestPayload] \App\Agent\DTO\AddInteractionDTO $dto, #[CurrentUser] User $agent, \App\Agent\Repository\AgentClientInteractionRepository $repo): JsonResponse
    {
        $client = $this->userRepository->find($id);
        if (!$client || $client->getReferrerAgent()?->getId() !== $agent->getId()) return $this->json(['error' => 'Клиент не найден'], 404);
        $interaction = new \App\Agent\Entity\AgentClientInteraction();
        $interaction->setAgent($agent)->setClient($client)->setType($dto->type)->setNotes($dto->notes)->setInteractionDate(new \DateTimeImmutable($dto->interactionDate));
        $repo->save($interaction, true);
        return $this->json(['message' => 'Взаимодействие добавлено', 'id' => $interaction->getId()], 201);
    }
}
