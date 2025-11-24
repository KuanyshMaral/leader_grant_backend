<?php
// src/Partner/Api/PartnerController.php

namespace App\Partner\Api;

use App\Partner\Service\PartnerService;
use App\User\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/partner', name: 'partner_')]
#[IsGranted('ROLE_PARTNER')]
class PartnerController extends AbstractController
{
    public function __construct(
        private readonly PartnerService $partnerService,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * Получить информацию о банке партнёра.
     * 
     * GET /api/partner/bank
     */
    #[Route('/bank', name: 'bank', methods: ['GET'])]
    public function getMyBank(#[CurrentUser] User $user): JsonResponse
    {
        try {
            $bank = $this->partnerService->getPartnerBank($user);

            return new JsonResponse([
                'id' => $bank->getId(),
                'name' => $bank->getName(),
                'status' => 'active' // TODO: Добавить поле status в Bank entity
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Получить список клиентов банка.
     * 
     * GET /api/partner/clients
     */
    #[Route('/clients', name: 'clients', methods: ['GET'])]
    public function getClients(#[CurrentUser] User $user): JsonResponse
    {
        try {
            $bank = $this->partnerService->getPartnerBank($user);
            $clients = $this->partnerService->getBankClients($bank);

            return new JsonResponse($clients);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Получить список агентов.
     * 
     * GET /api/partner/agents
     */
    #[Route('/agents', name: 'agents', methods: ['GET'])]
    public function getAgents(#[CurrentUser] User $user): JsonResponse
    {
        try {
            $bank = $this->partnerService->getPartnerBank($user);
            $agents = $this->partnerService->getBankAgents($bank);

            return new JsonResponse($agents);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Получить заявки банка.
     * 
     * GET /api/partner/applications
     */
    #[Route('/applications', name: 'applications', methods: ['GET'])]
    public function getApplications(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        try {
            $bank = $this->partnerService->getPartnerBank($user);
            
            $filters = [
                'product_type' => $request->query->get('product_type'),
                'status' => $request->query->get('status'),
                'limit' => (int)($request->query->get('limit') ?? 100),
                'offset' => (int)($request->query->get('offset') ?? 0),
            ];

            $applications = $this->partnerService->getBankApplications($bank, $filters);

            // Сериализуем с правильными группами
            $json = $this->serializer->serialize(['data' => $applications], 'json', [
                'groups' => ['app:read']
            ]);

            return new JsonResponse($json, 200, [], true);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Изменить статус заявки.
     * 
     * PATCH /api/partner/applications/{id}/status
     */
    #[Route('/applications/{id}/status', name: 'update_application_status', methods: ['PATCH'])]
    public function updateApplicationStatus(
        int $id,
        Request $request,
        #[CurrentUser] User $partner
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
            $newStatus = $data['status'] ?? null;

            if (!$newStatus) {
                return new JsonResponse(['error' => 'Статус не указан'], 400);
            }

            $application = $this->partnerService->updateApplicationStatus($id, $partner, $newStatus);

            return new JsonResponse([
                'message' => 'Статус обновлен',
                'application_id' => $application->getId(),
                'new_status' => $application->getStatus()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Добавить комментарий к заявке.
     * 
     * POST /api/partner/applications/{id}/comment
     */
    #[Route('/applications/{id}/comment', name: 'add_comment', methods: ['POST'])]
    public function addComment(
        int $id,
        Request $request,
        #[CurrentUser] User $partner
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
            $comment = $data['comment'] ?? null;

            if (!$comment) {
                return new JsonResponse(['error' => 'Комментарий не указан'], 400);
            }

            $this->partnerService->addApplicationComment($id, $partner, $comment);

            return new JsonResponse(['message' => 'Комментарий добавлен']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Получить документы заявки.
     * 
     * GET /api/partner/applications/{id}/documents
     */
    #[Route('/applications/{id}/documents', name: 'get_application_documents', methods: ['GET'])]
    public function getApplicationDocuments(
        int $id,
        #[CurrentUser] User $partner
    ): JsonResponse {
        try {
            $documents = $this->partnerService->getApplicationDocuments($id, $partner);

            return new JsonResponse(['data' => $documents]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Одобрить документ.
     * 
     * PATCH /api/partner/documents/{id}/approve
     */
    #[Route('/documents/{id}/approve', name: 'approve_document', methods: ['PATCH'])]
    public function approveDocument(
        int $id,
        #[CurrentUser] User $partner
    ): JsonResponse {
        try {
            $document = $this->partnerService->approveDocument($id, $partner);

            return new JsonResponse([
                'message' => 'Документ одобрен',
                'document_id' => $document->getId(),
                'status' => $document->getStatus()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Отклонить документ.
     * 
     * PATCH /api/partner/documents/{id}/reject
     */
    #[Route('/documents/{id}/reject', name: 'reject_document', methods: ['PATCH'])]
    public function rejectDocument(
        int $id,
        Request $request,
        #[CurrentUser] User $partner
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
            $reason = $data['reason'] ?? 'Причина не указана';

            $document = $this->partnerService->rejectDocument($id, $partner, $reason);

            return new JsonResponse([
                'message' => 'Документ отклонен',
                'document_id' => $document->getId(),
                'status' => $document->getStatus()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}
