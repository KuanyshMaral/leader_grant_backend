<?php
// src/Bank/Service/BankService.php

namespace App\Bank\Service;

use App\Bank\DTO\BankDTO;
use App\Bank\Entity\Bank;
use App\Bank\Exception\BankNotFoundException;
use App\Bank\Repository\BankRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class BankService
{
    public function __construct(
        private readonly BankRepository $bankRepository,
        private readonly EntityManagerInterface $entityManager, // Используем для flush
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Получает список всех банков (для админки или калькулятора).
     */
    public function listAllBanks(): array
    {
        return $this->bankRepository->findAll();
    }

    /**
     * Получает один банк по ID.
     * @throws BankNotFoundException
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
        // (Здесь можно добавить проверку, что банк с таким именем еще не существует)

        $bank = new Bank();
        $bank->setName($dto->name);
        $bank->setLogoPath($dto->logo_path);
        $bank->setConditions($dto->conditions);

        $this->bankRepository->save($bank, true); // (flush: true)

        $this->logger->info('Создан новый банк', [
            'bank_id' => $bank->getId(),
            'name' => $bank->getName()
        ]);

        return $bank;
    }

    /**
     * [ADMIN] Обновляет существующий банк.
     * @throws BankNotFoundException
     */
    public function updateBank(int $bankId, BankDTO $dto): Bank
    {
        $bank = $this->getBank($bankId); // (внутри уже есть проверка на 404)

        $bank->setName($dto->name);
        $bank->setLogoPath($dto->logo_path);
        $bank->setConditions($dto->conditions);

        $this->bankRepository->save($bank, true); // (flush: true)

        $this->logger->info('Банк обновлен', [
            'bank_id' => $bank->getId(),
            'name' => $bank->getName()
        ]);

        return $bank;
    }

    /**
     * [ADMIN] Частный случай обновления: только JSON с условиями.
     * @throws BankNotFoundException
     */
    public function updateBankConditions(int $bankId, array $conditions): Bank
    {
        $bank = $this->getBank($bankId);

        $bank->setConditions($conditions);
        $this->bankRepository->save($bank, true);

        $this->logger->info('Обновлены условия (тарифы) для банка', [
            'bank_id' => $bank->getId(),
        ]);

        return $bank;
    }
}
