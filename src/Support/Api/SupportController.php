<?php
// src/Support/Api/SupportController.php

namespace App\Support\Api;

use App\Support\DTO\CreateSupportTicketDTO;
use App\Support\Repository\SupportTicketRepository;
use App\Support\Entity\SupportTicket;
use App\Upload\Repository\UploadRepository;
use App\User\Entity\User;
use App\Shared\Api\BaseController;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/support')]
class SupportController extends BaseController
{
    public function __construct(
        LoggerInterface $logger,
        private readonly SupportTicketRepository $ticketRepository,
        private readonly UploadRepository $fileRepository
    ) {
        parent::__construct($logger);
    }

    /**
     * Create a new support ticket
     */
    #[Route('/tickets', methods: ['POST'])]
    public function createTicket(
        #[MapRequestPayload] CreateSupportTicketDTO $dto,
        #[CurrentUser] User $user,
        Request $request
    ): JsonResponse {
        $endpoint = 'POST /api/support/tickets';
        $this->logRequest($request, $endpoint);

        try {
            $ticket = new SupportTicket();
            $ticket->setUser($user);
            $ticket->setSubject($dto->subject);
            $ticket->setMessage($dto->message);

            // Attach file if provided
            if ($dto->attachment_id) {
                $file = $this->fileRepository->find($dto->attachment_id);
                if ($file) {
                    $ticket->setAttachment($file);
                }
            }

            $this->ticketRepository->save($ticket);

            $this->logResponse($endpoint, 201, [
                'ticket_id' => $ticket->getId(),
                'user_id' => $user->getId()
            ]);

            return $this->json($ticket, 201, [], ['groups' => 'support:read']);
        } catch (\Exception $e) {
            $this->logApiError($endpoint, $e, ['user_id' => $user->getId()]);
            throw $e;
        }
    }

    /**
     * Get all tickets for current user
     */
    #[Route('/tickets', methods: ['GET'])]
    public function getMyTickets(
        #[CurrentUser] User $user,
        Request $request
    ): JsonResponse {
        $endpoint = 'GET /api/support/tickets';
        $this->logRequest($request, $endpoint);

        try {
            $tickets = $this->ticketRepository->findByUser($user);

            $this->logResponse($endpoint, 200, [
                'user_id' => $user->getId(),
                'tickets_count' => count($tickets)
            ]);

            return $this->json(['data' => $tickets], 200, [], ['groups' => 'support:read']);
        } catch (\Exception $e) {
            $this->logApiError($endpoint, $e, ['user_id' => $user->getId()]);
            throw $e;
        }
    }

    /**
     * Get specific ticket
     */
    #[Route('/tickets/{id}', methods: ['GET'])]
    public function getTicket(
        int $id,
        #[CurrentUser] User $user,
        Request $request
    ): JsonResponse {
        $endpoint = "GET /api/support/tickets/{$id}";
        $this->logRequest($request, $endpoint);

        try {
            $ticket = $this->ticketRepository->findOneByIdAndUser($id, $user);

            if (!$ticket) {
                return new JsonResponse(['error' => 'Ticket not found'], 404);
            }

            $this->logResponse($endpoint, 200, ['ticket_id' => $id]);

            return $this->json($ticket, 200, [], ['groups' => 'support:read']);
        } catch (\Exception $e) {
            $this->logApiError($endpoint, $e, ['ticket_id' => $id]);
            throw $e;
        }
    }
}
