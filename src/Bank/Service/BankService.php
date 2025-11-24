<?php
// src/Bank/Service/BankService.php

namespace App\Bank\Service;

use App\Bank\DTO\BankDTO;
use App\Bank\Entity\Bank;
use App\Bank\Exception\BankNotFoundException;
use App\Bank\Repository\BankRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * ОПТИМИЗИРОВАНО: Использование BankCacheService и инвалидация кэша
 */
class BankService
{
    public function __construct(
        private readonly BankRepository $bankRepository,
        private readonly BankCacheService $cacheService, // ДОБАВЛЕНО
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * [ОПТИМИЗИРОВАНО] Получает список всех банков через кэш.
     */
    public function listAllBanks(): array
    {
        return $this->cacheService->getAllBanks();
    }

    /**
     * [НОВЫЙ] Получает только активные банки (для калькулятора).
     */
    public function listActiveBanks(): array
    {
        return $this->cacheService->getActiveBanks();
    }

    /**
     * Получает один банк по ID.
     */
    public function getBank(int $bankId): Bank
    {
        $bank = $this->bankRepository->find($bankId);
        if (!$bank) {
            throw new BankNotFoundException();
        }
        return $bank;
    }

    /**
     * [ADMIN] Создает новый банк в системе.
     */
    public function createBank(BankDTO $dto): Bank
    {
        $bank = new Bank();
        $bank->setName($dto->name);
        $bank->setLogoPath($dto->logo_path);
        $bank->setConditions($dto->conditions);
        $bank->setStatus('active');
        $bank->setAccreditationStatus('pending');

        $this->bankRepository->save($bank, true);
        
        // ДОБАВЛЕНО: Инвалидация кэша
        $this->cacheService->invalidateCache();

        $this->logger->info('Создан новый банк', [
            'bank_id' => $bank->getId(),
            'name' => $bank->getName()
        ]);

        return $bank;
    }

    /**
     * [ADMIN] Обновляет существующий банк.
     */
    public function updateBank(int $bankId, BankDTO $dto): Bank
    {
        $bank = $this->getBank($bankId);

        $bank->setName($dto->name);
        $bank->setLogoPath($dto->logo_path);
        $bank->setConditions($dto->conditions);

        $this->bankRepository->save($bank, true);
        
        // ДОБАВЛЕНО: Инвалидация кэша
        $this->cacheService->invalidateCache();

        $this->logger->info('Банк обновлен', [
            'bank_id' => $bank->getId(),
            'name' => $bank->getName()
        ]);

        return $bank;
    }

    /**
     * [ADMIN] Частный случай обновления: только JSON с условиями.
     */
    public function updateBankConditions(int $bankId, array $conditions): Bank
    {
        $bank = $this->getBank($bankId);

        $bank->setConditions($conditions);
        $this->bankRepository->save($bank, true);
        
        // ДОБАВЛЕНО: Инвалидация кэша
        $this->cacheService->invalidateCache();

        $this->logger->info('Обновлены условия (тарифы) для банка', [
            'bank_id' => $bank->getId(),
        ]);

        return $bank;
    }

    /**
     * [НОВЫЙ] Удалить банк (мягкое удаление через статус).
     */
    public function deleteBank(int $bankId): void
    {
        $bank = $this->getBank($bankId);
        $bank->setStatus('archived');
        $this->bankRepository->save($bank, true);
        
        // ДОБАВЛЕНО: Инвалидация кэша
        $this->cacheService->invalidateCache();

        $this->logger->warning('Банк архивирован', ['bank_id' => $bankId]);
    }

    /**
     * [НОВЫЙ] Приостановить банк.
     */
    public function suspendBank(int $bankId): Bank
    {
        $bank = $this->getBank($bankId);
        $bank->setStatus('suspended');
        $this->bankRepository->save($bank, true);
        
        // ДОБАВЛЕНО: Инвалидация кэша
        $this->cacheService->invalidateCache();

        $this->logger->warning('Банк приостановлен', ['bank_id' => $bankId]);

        return $bank;
    }

    /**
     * [НОВЫЙ] Активировать банк.
     */
    public function activateBank(int $bankId): Bank
    {
        $bank = $this->getBank($bankId);
        $bank->setStatus('active');
        $this->bankRepository->save($bank, true);
        
        // ДОБАВЛЕНО: Инвалидация кэша
        $this->cacheService->invalidateCache();

        $this->logger->info('Банк активирован', ['bank_id' => $bankId]);

        return $bank;
    }

    /**
     * [НОВЫЙ] Получить банки на аккредитации.
     */
    public function getPendingAccreditation(): array
    {
        return $this->bankRepository->findPendingAccreditation();
    }

    /**
     * [НОВЫЙ] Одобрить аккредитацию банка.
     */
    public function approveAccreditation(int $bankId): Bank
    {
        $bank = $this->getBank($bankId);
        $bank->setAccreditationStatus('approved');
        $bank->setAccreditationDate(new \DateTimeImmutable());
        $this->bankRepository->save($bank, true);
        
        // ДОБАВЛЕНО: Инвалидация кэша
        $this->cacheService->invalidateCache();

        $this->logger->info('Аккредитация банка одобрена', ['bank_id' => $bankId]);

        return $bank;
    }

    /**
     * [НОВЫЙ] Отклонить аккредитацию банка.
     */
    public function rejectAccreditation(int $bankId, string $reason): Bank
    {
        $bank = $this->getBank($bankId);
        $bank->setAccreditationStatus('rejected');
        $bank->setRejectionReason($reason);
        $this->bankRepository->save($bank, true);
        
        // ДОБАВЛЕНО: Инвалидация кэша
        $this->cacheService->invalidateCache();

        $this->logger->warning('Аккредитация банка отклонена', [
            'bank_id' => $bankId,
            'reason' => $reason
        ]);

        return $bank;
    }
}
