<?php
// src/Agent/Service/AgentCommissionService.php

namespace App\Agent\Service;

use App\Agent\Entity\AgentCommission;
use App\Agent\Repository\AgentCommissionRepository;
use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AgentCommissionService
{
    public function __construct(
        private readonly AgentCommissionRepository $commissionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Получить комиссии агента.
     */
    public function getAgentCommissions(User $agent): array
    {
        return $this->commissionRepository->findByAgent($agent);
    }

    /**
     * Получить все комиссии (для админа).
     */
    public function getAllCommissions(): array
    {
        return $this->commissionRepository->findAll();
    }

    /**
     * Отметить комиссию как выплаченную.
     */
    public function markAsPaid(int $commissionId): AgentCommission
    {
        $commission = $this->commissionRepository->find($commissionId);
        
        if (!$commission) {
            throw new \Exception('Комиссия не найдена');
        }

        $commission->setStatus('paid');
        $commission->setPaidAt(new \DateTimeImmutable());
        
        $this->entityManager->flush();

        $this->logger->info('Commission marked as paid', [
            'commission_id' => $commissionId,
            'agent_id' => $commission->getAgent()->getId(),
            'amount' => $commission->getCommissionAmount()
        ]);

        return $commission;
    }

    /**
     * Рассчитать комиссию для заявки (вызывается автоматически при одобрении).
     */
    public function calculateCommission(\App\Application\Entity\Application $application): ?AgentCommission
    {
        $agent = $application->getAgentUser();
        
        if (!$agent || !$agent->getCommissionRate()) {
            return null; // Нет агента или не установлена ставка
        }

        // Проверяем, не создана ли уже комиссия для этой заявки
        $existing = $this->entityManager->getRepository(AgentCommission::class)
            ->findOneBy(['application' => $application]);
        
        if ($existing) {
            return $existing; // Уже создана
        }

        $commission = new AgentCommission();
        $commission->setAgent($agent);
        $commission->setApplication($application);
        $commission->setCommissionRate($agent->getCommissionRate());
        
        // Рассчитываем сумму: amount * rate / 100
        $rate = (float) $agent->getCommissionRate();
        $amount = (float) $application->getAmount();
        $commissionAmount = ($amount * $rate) / 100;
        
        $commission->setCommissionAmount((string) $commissionAmount);
        $commission->setStatus('pending');

        $this->commissionRepository->save($commission, true);

        $this->logger->info('Commission calculated', [
            'commission_id' => $commission->getId(),
            'application_id' => $application->getId(),
            'agent_id' => $agent->getId(),
            'rate' => $rate,
            'amount' => $commissionAmount
        ]);

        return $commission;
    }
}
