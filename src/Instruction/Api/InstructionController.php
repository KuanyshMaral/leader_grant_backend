<?php
// src/Instruction/Api/InstructionController.php

namespace App\Instruction\Api;

use App\Instruction\Repository\InstructionRepository;
use App\Shared\Api\BaseController;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/instructions')]
class InstructionController extends BaseController
{
    public function __construct(
        LoggerInterface $logger,
        private readonly InstructionRepository $instructionRepository
    ) {
        parent::__construct($logger);
    }

    /**
     * Get all published instructions
     */
    #[Route('', methods: ['GET'])]
    public function getAll(Request $request): JsonResponse
    {
        $endpoint = 'GET /api/instructions';
        $this->logRequest($request, $endpoint);

        try {
            $instructions = $this->instructionRepository->findAllPublished();

            // Return list with excerpts
            $data = array_map(function ($instruction) {
                return [
                    'id' => $instruction->getId(),
                    'title' => $instruction->getTitle(),
                    'slug' => $instruction->getSlug(),
                    'category' => $instruction->getCategory(),
                    'excerpt' => $instruction->getExcerpt(),
                    'order_index' => $instruction->getOrderIndex(),
                ];
            }, $instructions);

            $this->logResponse($endpoint, 200, ['count' => count($instructions)]);

            return $this->json(['data' => $data], 200);
        } catch (\Exception $e) {
            $this->logApiError($endpoint, $e);
            throw $e;
        }
    }

    /**
     * Get specific instruction by slug
     */
    #[Route('/{slug}', methods: ['GET'])]
    public function getBySlug(string $slug, Request $request): JsonResponse
    {
        $endpoint = "GET /api/instructions/{$slug}";
        $this->logRequest($request, $endpoint);

        try {
            $instruction = $this->instructionRepository->findOneBySlug($slug);

            if (!$instruction) {
                return new JsonResponse(['error' => 'Instruction not found'], 404);
            }

            $data = [
                'id' => $instruction->getId(),
                'title' => $instruction->getTitle(),
                'slug' => $instruction->getSlug(),
                'content' => $instruction->getContent(),
                'category' => $instruction->getCategory(),
            ];

            $this->logResponse($endpoint, 200, ['slug' => $slug]);

            return $this->json($data,  200);
        } catch (\Exception $e) {
            $this->logApiError($endpoint, $e, ['slug' => $slug]);
            throw $e;
        }
    }
}
