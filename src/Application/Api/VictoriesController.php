<?php
// src/Application/Api/VictoriesController.php

namespace App\Application\Api;

use App\Application\Repository\ApplicationRepository;
use App\Shared\Api\BaseController;
use App\User\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Controller for client victories (successful applications).
 */
#[Route('/api/applications')]
class VictoriesController extends BaseController
{
    public function __construct(
        LoggerInterface $logger,
        private readonly ApplicationRepository $applicationRepository
    ) {
        parent::__construct($logger);
    }

    /**
     * Get victories (successful/approved applications) for the current client user.
     * Only accessible by CLIENT role users.
     */
    #[Route('/victories', methods: ['GET'])]
    public function getVictories(
        #[CurrentUser] User $user,
        Request $request
    ): JsonResponse {
        $endpoint = 'GET /api/applications/victories';
        $this->logRequest($request, $endpoint);

        try {
            // Only allow CLIENT role users to access this endpoint
            if ($user->getRole()->value !== 'client') {
                $this->logger->warning('Non-client user attempted to access victories endpoint', [
                    'user_id' => $user->getId(),
                    'user_role' => $user->getRole()->value
                ]);
                
                return new JsonResponse([
                    'error' => 'Access denied. This endpoint is only available for clients.'
                ], 403);
            }

            // Query for successful applications (approved or completed status)
            $qb = $this->applicationRepository->createQueryBuilder('a')
                // EAGER LOADING to prevent N+1 queries
                ->leftJoin('a.client_user', 'client')->addSelect('client')
                ->leftJoin('a.agent_user', 'agent')->addSelect('agent')
                ->leftJoin('a.bank', 'bank')->addSelect('bank')
                // Filter by current client user
                ->where('a.client_user = :user')
                ->setParameter('user', $user)
                // Filter by successful statuses (approved or completed)
                ->andWhere('a.status IN (:successful_statuses)')
                ->setParameter('successful_statuses', ['approved', 'completed'])
                // Order by most recent first
                ->orderBy('a.updated_at', 'DESC');

            $victories = $qb->getQuery()->getResult();

            $this->logResponse($endpoint, 200, [
                'user_id' => $user->getId(),
                'victories_count' => count($victories)
            ]);

            return $this->json($victories, 200, [], ['groups' => 'app:read']);
        } catch (\Exception $e) {
            $this->logApiError($endpoint, $e, ['user_id' => $user->getId()]);
            throw $e;
        }
    }
}
