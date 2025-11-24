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
        private readonly NewsRepository $newsRepository,
        private readonly \App\News\Service\NewsCacheService $newsCacheService // ДОБАВЛЕНО
    ) {}

    /**
     * [ОПТИМИЗИРОВАНО] Получить список новостей с кэшированием.
     */
    #[Route('', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function list(): JsonResponse
    {
        // ОПТИМИЗИРОВАНО: Используем кэш
        $news = $this->newsCacheService->getPublishedNews();
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

    /**
     * [ALL] Получить одну новость по ID.
     */
    #[Route('/{id}', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getOne(int $id): JsonResponse
    {
        $news = $this->newsRepository->find($id);

        if (!$news) {
            return $this->json(['error' => 'Новость не найдена'], 404);
        }

        return $this->json($news, 200, [], ['groups' => 'news:read']);
    }

    /**
     * [ADMIN] Обновить новость.
     */
    #[Route('/{id}', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(int $id, #[MapRequestPayload] CreateNewsDTO $dto): JsonResponse
    {
        $news = $this->newsRepository->find($id);

        if (!$news) {
            return $this->json(['error' => 'Новость не найдена'], 404);
        }

        $news->setTitle($dto->title);
        $news->setContent($dto->content);
        $news->setPublished($dto->is_published);

        $this->newsRepository->save($news, true);

        return $this->json($news, 200, [], ['groups' => 'news:read']);
    }

    /**
     * [ADMIN] Удалить новость.
     */
    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): JsonResponse
    {
        $news = $this->newsRepository->find($id);

        if (!$news) {
            return $this->json(['error' => 'Новость не найдена'], 404);
        }

        $this->newsRepository->remove($news, true);

        return $this->json(['message' => 'Новость удалена'], 200);
    }
}
