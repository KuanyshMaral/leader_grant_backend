<?php
// src/Partner/Service/PartnerService.php

namespace App\Partner\Service;

use App\User\Entity\User;
use App\User\Repository\UserRepository;
use App\Bank\Entity\Bank;
use App\Application\Repository\ApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * ОПТИМИЗИРОВАНО: Делегирование сложных запросов в UserRepository
 */
class PartnerService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ApplicationRepository $applicationRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Получить банк партнёра.
     */
    public function getPartnerBank(User $partner): Bank
    {
        $bank = $partner->getBank();

        if (!$bank) {
            throw new \Exception('У партнёра не указан банк');
        }

        return $bank;
    }

    /**
     * [ОПТИМИЗИРОВАНО] Получить клиентов банка с статистикой.
     * Делегировано в UserRepository для переиспользования.
     */
    public function getBankClients(Bank $bank): array
    {
        return $this->userRepository->findBankClientsWithStats($bank);
    }

    /**
     * [ОПТИМИЗИРОВАНО] Получить агентов банка с статистикой.
     * Делегировано в UserRepository для переиспользования.
     */
    public function getBankAgents(Bank $bank): array
    {
        $results = $this->userRepository->findBankAgentsWithStats($bank);

        // Добавляем рейтинг (пока заглушка)
        return array_map(function ($agent) {
            $agent['rating'] = 4.5; // TODO: Реализовать расчёт рейтинга
            return $agent;
        }, $results);
    }

    /**
     * [ОПТИМИЗИРОВАНО] Получить заявки банка с фильтрацией и EAGER LOADING.
     */
    public function getBankApplications(Bank $bank, array $filters = []): array
    {
        $qb = $this->applicationRepository->createQueryBuilder('a')
            // EAGER LOADING для предотвращения N+1
            ->leftJoin('a.client_user', 'client')->addSelect('client')
            ->leftJoin('a.agent_user', 'agent')->addSelect('agent')
            ->leftJoin('a.bank', 'b')->addSelect('b')
            ->where('a.bank = :bank')
            ->setParameter('bank', $bank)
            ->orderBy('a.created_at', 'DESC');

        // Фильтр по типу продукта
        if (!empty($filters['product_type'])) {
            $qb->andWhere('a.product_type = :product_type')
               ->setParameter('product_type', $filters['product_type']);
        }

        // Фильтр по статусу
        if (!empty($filters['status'])) {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $filters['status']);
        }

        // Пагинация
        $limit = $filters['limit'] ?? 100;
        $offset = $filters['offset'] ?? 0;
        
        $qb->setMaxResults($limit)
           ->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

    /**
     * Обновить статус заявки (только для заявок банка партнёра).
     */
    public function updateApplicationStatus(int $applicationId, User $partner, string $newStatus): \App\Application\Entity\Application
    {
        $bank = $this->getPartnerBank($partner);

        $application = $this->applicationRepository->find($applicationId);

        if (!$application) {
            throw new \Exception('Заявка не найдена');
        }

        // Проверка: заявка должна быть в банке партнёра
        if ($application->getBank()->getId() !== $bank->getId()) {
            throw new \Exception('Эта заявка не принадлежит вашему банку');
        }

        $application->setStatus($newStatus);
        $this->entityManager->flush();

        $this->logger->info('Партнёр изменил статус заявки', [
            'partner_id' => $partner->getId(),
            'application_id' => $applicationId,
            'new_status' => $newStatus
        ]);

        return $application;
    }

    /**
     * Добавить комментарий к заявке.
     */
    public function addCommentToApplication(int $applicationId, User $partner, string $comment): void
    {
        $bank = $this->getPartnerBank($partner);

        $application = $this->applicationRepository->find($applicationId);

        if (!$application) {
            throw new \Exception('Заявка не найдена');
        }

        if ($application->getBank()->getId() !== $bank->getId()) {
            throw new \Exception('Эта заявка не принадлежит вашему банку');
        }

        // TODO: Реализовать сохранение комментария (нужна Entity Comment или использовать Chat)
        
        $this->logger->info('Партнёр добавил комментарий к заявке', [
            'partner_id' => $partner->getId(),
            'application_id' => $applicationId,
            'comment' => $comment
        ]);
    }

    /**
     * [НОВЫЙ] Получить статистику банка.
     */
    public function getBankStatistics(Bank $bank): array
    {
        $qb = $this->applicationRepository->createQueryBuilder('a')
            ->select('
                COUNT(a.id) as total_applications,
                COALESCE(SUM(a.amount), 0) as total_amount,
                COALESCE(SUM(CASE WHEN a.status = :approved THEN a.amount ELSE 0 END), 0) as approved_amount,
                COUNT(CASE WHEN a.status = :approved THEN 1 END) as approved_count,
                COUNT(CASE WHEN a.status = :rejected THEN 1 END) as rejected_count,
                COUNT(CASE WHEN a.status = :pending THEN 1 END) as pending_count
            ')
            ->where('a.bank = :bank')
            ->setParameter('bank', $bank)
            ->setParameter('approved', 'approved')
            ->setParameter('rejected', 'rejected')
            ->setParameter('pending', 'pending');

        return $qb->getQuery()->getSingleResult();
    }
}
