<?php
// src/Admin/Api/AdminController.php

namespace App\Admin\Api;

use App\Admin\Service\AdminService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly AdminService $adminService,
        private readonly SerializerInterface $serializer
    ) {
    }

    // ============ CHAT MODERATION ============

    /**
     * Получить список заявок с pending сообщениями.
     * 
     * GET /admin/moderation/applications
     */
    #[Route('/moderation/applications', name: 'moderation_applications', methods: ['GET'])]
    public function getApplicationsWithPendingMessages(): JsonResponse
    {
        $applications = $this->adminService->getApplicationsWithPendingMessages();
        return new JsonResponse($applications);
    }

    /**
     * Получить список сообщений на модерации.
     * 
     * GET /admin/chat/pending
     */
    #[Route('/chat/pending', name: 'chat_pending', methods: ['GET'])]
    public function getPendingMessages(): JsonResponse
    {
        $messages = $this->adminService->getPendingMessages();

        $json = $this->serializer->serialize($messages, 'json', ['groups' => ['chat:read']]);

        return new JsonResponse($json, 200, [], true);
    }

    /**
     * Одобрить сообщение.
     * 
     * POST /admin/chat/messages/{id}/approve
     */
    #[Route('/chat/messages/{id}/approve', name: 'chat_approve', methods: ['POST'])]
    public function approveMessage(int $id): JsonResponse
    {
        try {
            $admin = $this->getUser();
            $this->adminService->approveMessage($id, $admin);

            return new JsonResponse(['success' => true, 'message' => 'Сообщение одобрено']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Отклонить сообщение.
     * 
     * POST /admin/chat/messages/{id}/reject
     */
    #[Route('/chat/messages/{id}/reject', name: 'chat_reject', methods: ['POST'])]
    public function rejectMessage(int $id): JsonResponse
    {
        try {
            $admin = $this->getUser();
            $this->adminService->rejectMessage($id, $admin);

            return new JsonResponse(['success' => true, 'message' => 'Сообщение отклонено']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    // ============ USER ACCREDITATION ============

    /**
     * Получить список пользователей на аккредитации.
     * 
     * GET /admin/users/accreditation/pending
     */
    #[Route('/users/accreditation/pending', name: 'users_accreditation_pending', methods: ['GET'])]
    public function getPendingAccreditations(): JsonResponse
    {
        $users = $this->adminService->getPendingAccreditations();

        // Преобразуем в массив с включением company_name и inn
        $usersData = array_map(function ($user) {
            $company = $user->getCompany();
            return [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'fio' => $user->getFio(),
                'role' => $user->getRole(),
                'status' => $user->getStatus(),
                'accreditationStatus' => $user->getAccreditationStatus(),
                'company_name' => $company ? $company->getName() : null,
                'inn' => $company ? $company->getInn() : null,
                'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $users);

        return new JsonResponse($usersData);
    }

    /**
     * Одобрить аккредитацию пользователя.
     * 
     * POST /admin/users/{id}/approve-accreditation
     */
    #[Route('/users/{id}/approve-accreditation', name: 'users_approve_accreditation', methods: ['POST'])]
    public function approveAccreditation(int $id): JsonResponse
    {
        try {
            $admin = $this->getUser();
            $this->adminService->approveAccreditation($id, $admin);

            return new JsonResponse(['success' => true, 'message' => 'Аккредитация одобрена']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Отклонить аккредитацию пользователя.
     * 
     * POST /admin/users/{id}/reject-accreditation
     * Body: { "reason": "причина отказа" }
     */
    #[Route('/users/{id}/reject-accreditation', name: 'users_reject_accreditation', methods: ['POST'])]
    public function rejectAccreditation(int $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $reason = $data['reason'] ?? 'Не указана';

            $admin = $this->getUser();
            $this->adminService->rejectAccreditation($id, $admin, $reason);

            return new JsonResponse(['success' => true, 'message' => 'Аккредитация отклонена']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Получить документы пользователя.
     * 
     * GET /admin/users/{id}/documents
     */
    #[Route('/users/{id}/documents', name: 'users_documents', methods: ['GET'])]
    public function getUserDocuments(int $id): JsonResponse
    {
        try {
            $documents = $this->adminService->getUserDocuments($id);
            
            $documentsData = array_map(function ($doc) {
                return [
                    'id' => $doc->getId(),
                    'file_name' => $doc->getFileName(),
                    'doc_type' => $doc->getDocType()->value,
                    'url' => $doc->getPublicPath(),
                    'uploaded_at' => $doc->getCreatedAt()->format('c'),
                ];
            }, $documents);

            return new JsonResponse($documentsData);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    // ============ PARTNER CREATION ============

    /**
     * Получить список банков.
     * 
     * GET /admin/banks
     */
    #[Route('/banks', name: 'banks_list', methods: ['GET'])]
    public function getBanks(): JsonResponse
    {
        $banks = $this->adminService->getAllBanks();

        $banksData = array_map(function ($bank) {
            return [
                'id' => $bank->getId(),
                'name' => $bank->getName(),
            ];
        }, $banks);

        return new JsonResponse($banksData);
    }

    /**
     * Создать партнёра (сотрудника банка).
     * 
     * POST /admin/users/create-partner
     * Body: { "bank_id": 1, "email": "...", "fio": "...", "phone": "...", "password": "..." }
     */
    #[Route('/users/create-partner', name: 'users_create_partner', methods: ['POST'])]
    public function createPartner(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $admin = $this->getUser();

            $user = $this->adminService->createPartner($data, $admin);

            return new JsonResponse([
                'success' => true,
                'message' => 'Партнёр создан',
                'user_id' => $user->getId()
            ], 201);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}
