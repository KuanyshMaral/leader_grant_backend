<?php
// src/Application/Service/CalculatorService.php

namespace App\Application\Service;

use App\Application\DTO\CalculatorRequestDTO;
use App\Application\DTO\ProductDataDTO;
use App\Bank\Entity\Bank;
use App\Bank\Repository\BankRepository;
use Psr\Log\LoggerInterface;

class CalculatorService
{
    public function __construct(
        private readonly BankRepository $bankRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Выполняет расчет и фильтрацию банков на основе данных DTO.
     * Возвращает структурированный массив с одобренными и отклоненными банками.
     */
    public function calculate(CalculatorRequestDTO $dto): array
    {
        $allBanks = $this->bankRepository->findAll();

        $approvedBanks = [];
        $rejectedBanks = [];

        foreach ($allBanks as $bank) {
            $conditions = $bank->getConditions(); // Получаем JSON-поле с правилами
            $reason = $this->checkBankConditions($bank, $conditions, $dto);

            if ($reason === null) {
                // Все проверки пройдены
                $approvedBanks[] = [
                    'bank_id' => $bank->getId(),
                    'name' => $bank->getName(),
                    'logo_path' => $bank->getLogoPath(),
                    'tariff' => $conditions['tariffs'][$dto->product_type]['rate'] ?? 'н/д', // ИСПРАВЛЕНО
                    'decision_speed' => $conditions['speed'] ?? 'Средняя', // Пример
                ];
            } else {
                // Банк не прошел проверку
                $rejectedBanks[] = [
                    'bank_id' => $bank->getId(),
                    'name' => $bank->getName(),
                    'reason' => $reason, // Причина отказа
                ];
            }
        }

        $this->logger->info('Расчет калькулятора выполнен', [
            'product' => $dto->product_type,
            'amount' => $dto->amount,
            'approved_count' => count($approvedBanks),
            'rejected_count' => count($rejectedBanks),
        ]);

        return [
            'approved_banks' => $approvedBanks,
            'rejected_banks' => $rejectedBanks,
        ];
    }

    /**
     * Вспомогательный метод-валидатор.
     * Возвращает null, если банк подходит, или string (причину отказа), если нет.
     */
    private function checkBankConditions(Bank $bank, array $conditions, CalculatorRequestDTO $dto): ?string
    {
        // 1. Проверка на поддержку продукта
        if (!isset($conditions['products']) || !in_array($dto->product_type, $conditions['products'])) {
            return 'Банк не работает с данным типом продукта';
        }

        // 2. Проверка стоп-факторов (упрощенная)
        if (in_array($dto->product_data->client_inn, $conditions['stop_factors']['inn_blacklist'] ?? [])) {
            return 'Компания в стоп-листе банка';
        }

        // 3. Проверка по сумме
        $limits = $conditions['limits'][$dto->product_type] ?? null;
        if ($limits) {
            if (isset($limits['min_amount']) && $dto->amount < $limits['min_amount']) {
                return sprintf('Сумма %.2f руб. меньше минимальной (%.2f руб.)', $dto->amount, $limits['min_amount']);
            }
            if (isset($limits['max_amount']) && $dto->amount > $limits['max_amount']) {
                return sprintf('Сумма %.2f руб. больше максимальной (%.2f руб.)', $dto->amount, $limits['max_amount']);
            }
        }

        // 4. Проверка по сроку (РЕАЛИЗОВАНО)
        if ($limits) {
            if (isset($limits['min_term_days']) && $dto->term_days < $limits['min_term_days']) {
                return sprintf('Срок %d дн. меньше минимального (%d дн.)', $dto->term_days, $limits['min_term_days']);
            }
            if (isset($limits['max_term_days']) && $dto->term_days > $limits['max_term_days']) {
                return sprintf('Срок %d дн. больше максимального (%d дн.)', $dto->term_days, $limits['max_term_days']);
            }
        }

        // 5. ... Другие проверки (44-ФЗ/223-ФЗ, наличие аванса и т.д.) ...
        if ($dto->product_data->law === '44-ФЗ' && !($conditions['laws']['44_fz'] ?? false)) {
            return 'Банк не работает по 44-ФЗ';
        }

        // Если все проверки пройдены
        return null;
    }
}
