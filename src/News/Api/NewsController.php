<?php
// src/News/Api/NewsController.php

namespace App\News\Api;

use App\News\DTO\CreateNewsDTO;
use App\News\Entity\News;
use App\News\Repository\NewsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/news')]
class NewsController extends AbstractController
{
    public function __construct(
        private readonly NewsRepository $newsRepository
    ) {}

    /**
     * Получить список новостей (доступно всем авторизованным).
     */
    #[Route('', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function list(): JsonResponse
    {
        $news = $this->newsRepository->findPublished();
        return $this->json($news, 200, [], ['groups' => 'news:read']);
    }

    /**
     * [ADMIN] Создать новость.
     */
    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(#[MapRequestPayload] CreateNewsDTO $dto): JsonResponse
    {
        $news = new News();
        $news->setTitle($dto->title);
        $news->setContent($dto->content);
        $news->setPublished($dto->is_published);

        $this->newsRepository->save($news, true);

        return $this->json($news, 201, [], ['groups' => 'news:read']);
    }
}
