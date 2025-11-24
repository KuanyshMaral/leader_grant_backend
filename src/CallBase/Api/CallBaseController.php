<?php

namespace App\CallBase\Api;

use App\CallBase\Service\LeadService;
use App\Shared\Api\BaseController;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/callbase')]
class CallBaseController extends BaseController
{
    public function __construct(
        LoggerInterface $logger,
        private readonly LeadService $leadService
    ) {
        parent::__construct($logger);
    }
    
    /**
     * GET /api/callbase/leads
     */
    #[Route('/leads', methods: ['GET'])]
    public function getLeads(Request $request): JsonResponse
    {
        $endpoint = 'GET /api/callbase/leads';
        $this->logRequest($request, $endpoint);
        
        $user = $request->attributes->get('user');
        
        if (!$user || $user->getRole() !== 'agent') {
            return $this->errorResponse($endpoint, 'Access denied', 403, [
                'user_id' => $user?->getId()
            ]);
        }
        
        try {
            $leads = $this->leadService->getLeadsForAgent($user->getId());
            
            $this->logResponse($endpoint, 200, [
                'leads_count' => count($leads)
            ]);
            
            return new JsonResponse($leads);
        } catch (\Exception $e) {
            $this->logApiError($endpoint, $e, ['user_id' => $user->getId()]);
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * PATCH /api/callbase/leads/{id}/status
     */
    #[Route('/leads/{id}/status', methods: ['PATCH'])]
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $endpoint = "PATCH /api/callbase/leads/{$id}/status";
        $this->logRequest($request, $endpoint);
        
        $user = $request->attributes->get('user');
        
        if (!$user || $user->getRole() !== 'agent') {
            return $this->errorResponse($endpoint, 'Access denied', 403);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['status'])) {
            return $this->errorResponse($endpoint, 'Status is required', 400, [
                'lead_id' => $id
            ]);
        }
        
        try {
            $this->leadService->updateStatus($id, $data['status']);
            
            $this->logResponse($endpoint, 200, [
                'lead_id' => $id,
                'new_status' => $data['status']
            ]);
            
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            $this->logApiError($endpoint, $e, [
                'lead_id' => $id,
                'status' => $data['status']
            ]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
    
    /**
     * PATCH /api/callbase/leads/{id}/comment
     */
    #[Route('/leads/{id}/comment', methods: ['PATCH'])]
    public function updateComment(int $id, Request $request): JsonResponse
    {
        $endpoint = "PATCH /api/callbase/leads/{$id}/comment";
        $this->logRequest($request, $endpoint);
        
        $user = $request->attributes->get('user');
        
        if (!$user || $user->getRole() !== 'agent') {
            return $this->errorResponse($endpoint, 'Access denied', 403);
        }
        
        $data = json_decode($request->getContent(), true);
        
        try {
            $this->leadService->updateComment($id, $data['comment'] ?? '');
            
            $this->logResponse($endpoint, 200, ['lead_id' => $id]);
            
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            $this->logApiError($endpoint, $e, ['lead_id' => $id]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
    
    /**
     * POST /api/callbase/leads/{id}/convert
     */
    #[Route('/leads/{id}/convert', methods: ['POST'])]
    public function convertToApplication(int $id, Request $request): JsonResponse
    {
        $endpoint = "POST /api/callbase/leads/{$id}/convert";
        $this->logRequest($request, $endpoint);
        
        $user = $request->attributes->get('user');
        
        if (!$user || $user->getRole() !== 'agent') {
            return $this->errorResponse($endpoint, 'Access denied',  403);
        }
        
        $data = json_decode($request->getContent(), true);
        
        try {
            $appId = $this->leadService->convertLeadToApplication(
                $id,
                $user->getId(),
                $data ?? []
            );
            
            $this->logResponse($endpoint, 200, [
                'lead_id' => $id,
                'application_id' => $appId
            ]);
            
            return new JsonResponse([
                'success' => true,
                'application_id' => $appId,
            ]);
        } catch (\Exception $e) {
            $this->logApiError($endpoint, $e, ['lead_id' => $id]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}
