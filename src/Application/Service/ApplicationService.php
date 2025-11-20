<?php
// src/Application/Service/ApplicationService.php

namespace App\Application\Service;

use App\Application\DTO\CreateApplicationDTO;
use App\Application\DTO\UpdateApplicationStatusDTO;
use App\Application\Entity\Application;
use App\Application\Event\ApplicationCreatedEvent;
use App\Application\Event\ApplicationStatusChangedEvent;
use App\Application\Exception\ApplicationAccessDeniedException;
use App\Application\Exception\ApplicationNotFoundException;
use App\Application\Repository\ApplicationRepository;
use App\Bank\Repository\BankRepository;
use App\Shared\DTO\PaginationRequestDTO;
use App\User\Entity\User;
use App\User\Exception\UserNotFoundException;
use App\User\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class ApplicationService
{
    public function __construct(
        private readonly ApplicationRepository $applicationRepository,
        private readonly UserRepository $userRepository,
        private readonly BankRepository $bankRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $bus
    ) {
    }

    /**
     * Создает заявки. + ЛОГИКА КРОСС-ПРОДАЖИ (КРЕДИТ)
     * @param User $creator
     * @return Application[]
     */
    public function createApplications(CreateApplicationDTO $dto, User $creator): array
    {
        $client = $this->userRepository->find($dto->client_user_id);
        if (!$client) {
            throw new UserNotFoundException();
        }
        $agent = ($creator->getRole() === 'agent') ? $creator : null;

        $createdApplications = [];

        // 1. Создаем ОСНОВНЫЕ заявки (например, БГ)
        $mainApps = $this->createApplicationsBatch($dto, $client, $agent);
        $createdApplications = array_merge($createdApplications, $mainApps);

        // 2. ЛОГИКА КРОСС-ПРОДАЖИ: Если это БГ и стоит галочка "Нужен кредит"
        // (Предполагается, что в ProductDataDTO есть поле need_credit)
        if ($dto->product_type === 'bank_guarantee' && ($dto->product_data->need_credit ?? false)) {
            // Создаем дубликаты заявок, но с типом 'credit'
            // Клонируем DTO и меняем тип
            $creditDto = clone $dto;
            $creditDto->product_type = 'credit'; // Меняем тип на Кредит

            $creditApps = $this->createApplicationsBatch($creditDto, $client, $agent);
            $createdApplications = array_merge($createdApplications, $creditApps);

            $this->logger->info('Автоматически созданы заявки на Кредит (кросс-продажа)', ['count' => count($creditApps)]);
        }

        $this->entityManager->flush();

        // Уведомление админа
        $appIds = array_map(fn($app) => $app->getId(), $createdApplications);
        if (!empty($appIds)) {
            $this->bus->dispatch(new ApplicationCreatedEvent($appIds, $client->getId()));
        }

        return $createdApplications;
    }

    /**
     * Вспомогательный метод для создания пачки заявок
     */
    private function createApplicationsBatch(CreateApplicationDTO $dto, User $client, ?User $agent): array
    {
        $apps = [];
        foreach ($dto->bank_ids as $bankId) {
            $bank = $this->bankRepository->find($bankId);
            if (!$bank) {
                $this->logger->warning('Попытка создать заявку с несуществующим bank_id', ['bank_id' => $bankId]);
                continue;
            }

            $app = new Application();
            $app->setClientUser($client);
            $app->setAgentUser($agent);
            $app->setBank($bank);
            $app->setProductType($dto->product_type);
            $app->setStatus('draft');
            $app->setAmount($dto->amount);
            $app->setTermDays($dto->term_days);
            $app->setProductData((array)$dto->product_data);

            $this->applicationRepository->save($app);
            $apps[] = $app;
        }
        return $apps;
    }

    public function getApplicationForUser(int $applicationId, User $user): Application
    {
        $application = $this->applicationRepository->find($applicationId);

        if (!$application) {
            throw new ApplicationNotFoundException();
        }

        if ($this->canUserView($user, $application)) {
            return $application;
        }

        throw new ApplicationAccessDeniedException();
    }

    private function canUserView(User $user, Application $application): bool
    {
        $role = $user->getRole();

        if ($role === 'admin') return true;
        if ($role === 'client' && $application->getClientUser()->getId() === $user->getId()) return true;
        if ($role === 'agent' && $application->getAgentUser()?->getId() === $user->getId()) return true;
        if ($role === 'partner' && $application->getBank()->getId() === $user->getCompany()?->getId()) return true;

        return false;
    }

    /**
     * [ОБНОВЛЕНО] Получает список заявок с ФИЛЬТРАЦИЕЙ.
     * Добавлены аргументы $statusFilter и $productFilter
     */
    public function listForUser(
        User $user,
        PaginationRequestDTO $pagination,
        ?string $statusFilter = null, // 'active', 'rejected', 'archive'
        ?string $productFilter = null // 'bank_guarantee', 'credit'...
    ): array
    {
        $qb = $this->applicationRepository->createQueryBuilder('a');

        // 1. Фильтр по Роли
        $role = $user->getRole();
        if ($role === 'client') {
            $qb->andWhere('a.client_user = :user')->setParameter('user', $user);
        } elseif ($role === 'agent') {
            $qb->andWhere('a.agent_user = :user')->setParameter('user', $user);
        } elseif ($role === 'partner') {
            // Используем прямую связь User -> Bank
            if ($user->getBank()) {
                $qb->andWhere('a.bank = :bankId')->setParameter('bankId', $user->getBank()->getId());
            } else {
                $qb->andWhere('1 = 0');
            }
        }

        // 2. Фильтр по Статусу (Табы в UI)
        if ($statusFilter) {
            if ($statusFilter === 'rejected') {
                $qb->andWhere('a.status = :st')->setParameter('st', 'rejected');
            } elseif ($statusFilter === 'archive') {
                // Архивные - это завершенные или архивированные
                $qb->andWhere('a.status IN (:sts)')->setParameter('sts', ['completed', 'archived']);
            } else {
                // 'active' (Активные) - все, кроме отказа, завершенных и архива
                $qb->andWhere('a.status NOT IN (:sts)')->setParameter('sts', ['rejected', 'completed', 'archived']);
            }
        }

        // 3. Фильтр по Продукту
        if ($productFilter) {
            $qb->andWhere('a.product_type = :pt')->setParameter('pt', $productFilter);
        }

        // Подсчет total
        $countQb = clone $qb;
        $total = $countQb->select('count(a.id)')->getQuery()->getSingleScalarResult();

        // Пагинация и сортировка
        $qb->orderBy('a.updated_at', 'DESC')
            ->setFirstResult(($pagination->page - 1) * $pagination->limit)
            ->setMaxResults($pagination->limit);

        $applications = $qb->getQuery()->getResult();

        return [
            'data' => $applications,
            'total' => (int) $total,
            'page' => $pagination->page,
            'limit' => $pagination->limit,
        ];
    }

    /**
     * [РЕАЛИЗОВАНО] Обновляет статус заявки (для Админов/Партнеров).
     */
    public function updateStatus(
        int $applicationId,
        UpdateApplicationStatusDTO $dto,
        User $updater
    ): Application {

        $application = $this->applicationRepository->find($applicationId);
        if (!$application) {
            throw new ApplicationNotFoundException();
        }

        // 1. Проверяем права на ИЗМЕНЕНИЕ
        $role = $updater->getRole();
        $isPartner = ($role === 'partner' && $updater->getBank() === $application->getBank()); // Используем связь User->Bank

        if ($role !== 'admin' && !$isPartner) {
            $this->logger->warning('Попытка сменить статус заявки без прав', [
                'app_id' => $applicationId, 'user_id' => $updater->getId()
            ]);
            throw new ApplicationAccessDeniedException('Только Админ или Партнер банка могут менять статус.');
        }

        $oldStatus = $application->getStatus();
        $application->setStatus($dto->status);

        // 4. Если пришла оферта, сохраняем ставку и комиссию
        if ($dto->status === 'offer_received') {
            if ($dto->tariff_rate !== null) {
                $application->setTariffRate($dto->tariff_rate);
            }
            if ($dto->commission_amount !== null) {
                // Преобразуем float в string для decimal
                $application->setCommissionAmount((string)$dto->commission_amount);
            }
            // Если есть доп. данные оферты
            if ($dto->offer_data) {
                $application->setOfferData($dto->offer_data);
            }
        }

        // 5. Если отказ или возврат на доработку
        if (($dto->status === 'rejected' || $dto->status === 'returned_for_revision') && $dto->rejection_reason) {
            $productData = $application->getProductData();
            $productData['rejection_reason'] = $dto->rejection_reason;
            $application->setProductData($productData);
        }

        $this->applicationRepository->save($application, true);

        $this->logger->info('Статус заявки обновлен', [
            'app_id' => $application->getId(),
            'updater_id' => $updater->getId(),
            'old_status' => $oldStatus,
            'new_status' => $dto->status,
        ]);

        // "Кричим" в очередь
        $this->bus->dispatch(new ApplicationStatusChangedEvent(
            $application->getId(),
            $oldStatus,
            $dto->status
        ));

        return $application;
    }
}
