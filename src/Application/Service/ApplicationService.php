<?php
// src/Application/Service/ApplicationService.php

namespace App\Application\Service;

use App\Application\DTO\CreateApplicationDTO;
use App\Application\DTO\UpdateApplicationStatusDTO; // <-- ДОБАВЛЕНО
use App\Application\Entity\Application;
use App\Application\Event\ApplicationCreatedEvent; // <-- ДОБАВЛЕНО
use App\Application\Event\ApplicationStatusChangedEvent; // <-- ДОБАВЛЕНО
use App\Application\Exception\ApplicationAccessDeniedException;
use App\Application\Exception\ApplicationNotFoundException;
use App\Application\Repository\ApplicationRepository;
use App\Bank\Repository\BankRepository;
use App\Shared\DTO\PaginationRequestDTO; // <-- ДОБАВЛЕНО
use App\User\Entity\User;
use App\User\Exception\UserNotFoundException;
use App\User\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface; // <-- ДОБАВЛЕНО

class ApplicationService
{
    public function __construct(
        private readonly ApplicationRepository $applicationRepository,
        private readonly UserRepository $userRepository,
        private readonly BankRepository $bankRepository,
        private readonly EntityManagerInterface $entityManager, // Нужен для flush()
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $bus // <-- ИНЪЕКЦИЯ $bus ДОБАВЛЕНА
    ) {
    }

    /**
     * Создает одну или несколько заявок на основе выбора в калькуляторе.
     * @param User $creator - Тот, кто нажал кнопку (может быть Агентом или самим Клиентом)
     * @return Application[] - Массив созданных заявок
     */
    public function createApplications(CreateApplicationDTO $dto, User $creator): array
    {
        // 1. Находим Клиента (от чьего имени заявка)
        $client = $this->userRepository->find($dto->client_user_id);
        if (!$client) {
            throw new UserNotFoundException();
        }

        // 2. Определяем, кто Агент (если он есть)
        $agent = ($creator->getRole() === 'agent') ? $creator : null;

        // (Здесь должна быть проверка, что Агент имеет право подавать за этого Клиента)

        $createdApplications = [];

        // 3. Создаем заявки в цикле
        foreach ($dto->bank_ids as $bankId) {
            $bank = $this->bankRepository->find($bankId);
            if (!$bank) {
                $this->logger->warning('Попытка создать заявку с несуществующим bank_id', ['bank_id' => $bankId]);
                continue; // Пропускаем этот банк
            }

            $app = new Application();
            $app->setClientUser($client);
            $app->setAgentUser($agent); // Может быть null
            $app->setBank($bank);

            $app->setProductType($dto->product_type);
            $app->setStatus('draft'); // Начальный статус - "Черновик" или "Новая"
            $app->setAmount($dto->amount);
            $app->setTermDays($dto->term_days);

            // Сохраняем все данные из DTO (№ закупки, ИНН заказчика и т.д.)
            $app->setProductData((array)$dto->product_data);

            $this->applicationRepository->save($app); // Пока без flush()
            $createdApplications[] = $app;
        }

        // 4. Завершаем транзакцию
        $this->entityManager->flush();

        $this->logger->info('Новые заявки созданы', [
            'count' => count($createdApplications),
            'client_id' => $client->getId(),
            'agent_id' => $agent?->getId(),
        ]);

        // --- [РЕАЛИЗАЦИЯ ПРОБЕЛА] ---
        // "Кричим" в очередь, чтобы уведомить Админа
        $appIds = array_map(fn($app) => $app->getId(), $createdApplications);
        if (!empty($appIds)) {
            $this->bus->dispatch(new ApplicationCreatedEvent($appIds, $client->getId()));
        }
        // --- КОНЕЦ ---

        return $createdApplications;
    }

    /**
     * Получает одну заявку с проверкой прав доступа.
     * @throws ApplicationNotFoundException
     * @throws ApplicationAccessDeniedException
     */
    public function getApplicationForUser(int $applicationId, User $user): Application
    {
        $application = $this->applicationRepository->find($applicationId);

        if (!$application) {
            throw new ApplicationNotFoundException();
        }

        // Проверяем права доступа
        if ($this->canUserView($user, $application)) {
            return $application;
        }

        throw new ApplicationAccessDeniedException();
    }

    /**
     * Проверяет, имеет ли $user право видеть $application
     */
    private function canUserView(User $user, Application $application): bool
    {
        $role = $user->getRole();

        if ($role === 'admin') {
            return true; // Админ видит всё
        }

        if ($role === 'client' && $application->getClientUser()->getId() === $user->getId()) {
            return true; // Это заявка самого клиента
        }

        if ($role === 'agent' && $application->getAgentUser()?->getId() === $user->getId()) {
            return true; // Это заявка, которую создал агент
        }

        if ($role === 'partner' && $application->getBank()->getId() === $user->getCompany()?->getId()) {
            // (Предполагаем, что User-Партнер привязан к Company, а Company.id == Bank.id)
            // Эту логику нужно будет уточнить, но пока она такая.
            return true;
        }

        return false;
    }

    // --- РЕАЛИЗОВАННЫЕ МЕТОДЫ ---

    /**
     * [РЕАЛИЗОВАНО] Получает список заявок для пользователя с пагинацией.
     * Фильтрует список в зависимости от роли (Клиент, Агент, Партнер, Админ).
     *
     * @return array ['data' => Application[], 'total' => int]
     */
    public function listForUser(User $user, PaginationRequestDTO $pagination): array
    {
        // 1. Создаем QueryBuilder БЕЗ пагинации и сортировки
        $qb = $this->applicationRepository->createQueryBuilder('a');

        // 2. Применяем ВСЮ логику фильтрации
        $role = $user->getRole();

        if ($role === 'client') {
            $qb->andWhere('a.client_user = :user')
                ->setParameter('user', $user);
        } elseif ($role === 'agent') {
            $qb->andWhere('a.agent_user = :user')
                ->setParameter('user', $user);
        } elseif ($role === 'partner') {
            // (Теперь мы используем правильную связь, которую добавили в User.php)
            if ($user->getBank()) {
                $qb->andWhere('a.bank = :bankId')
                    ->setParameter('bankId', $user->getBank()->getId());
            } else {
                // Партнер не привязан к банку? Возвращаем 0 заявок.
                $qb->andWhere('1 = 0');
            }
        }
        // Админ (else) не имеет доп. фильтров - он видит ВСЕ.

        // 3. ПОЛУЧАЕМ TOTAL (до пагинации)
        // Клонируем $qb, чтобы наш SELECT не "сломал" основной запрос
        $countQb = clone $qb;
        $total = $countQb->select('count(a.id)')->getQuery()->getSingleScalarResult();

        // 4. ТЕПЕРЬ применяем пагинацию и сортировку
        $qb->orderBy('a.updated_at', 'DESC')
            ->setFirstResult(($pagination->page - 1) * $pagination->limit)
            ->setMaxResults($pagination->limit);

        $applications = $qb->getQuery()->getResult();

        return [
            'data' => $applications,
            'total' => (int) $total, // Приводим к int
            'page' => $pagination->page,
            'limit' => $pagination->limit,
        ];
    }

    /**
     * [РЕАЛИЗОВАНО] Обновляет статус заявки (для Админов/Партнеров).
     *
     * @throws ApplicationNotFoundException
     * @throws ApplicationAccessDeniedException
     */
    public function updateStatus(
        int $applicationId,
        UpdateApplicationStatusDTO $dto,
        User $updater // Тот, кто меняет статус
    ): Application {

        $application = $this->applicationRepository->find($applicationId);
        if (!$application) {
            throw new ApplicationNotFoundException();
        }

        // 1. Проверяем права на ИЗМЕНЕНИЕ (только Админ или Партнер этого банка)
        $role = $updater->getRole();
        // TODO: Уточнить логику привязки Партнера к Банку
        $isPartner = ($role === 'partner' && $application->getBank()->getId() === $updater->getCompany()?->getId());

        if ($role !== 'admin' && !$isPartner) {
            $this->logger->warning('Попытка сменить статус заявки без прав', [
                'app_id' => $applicationId, 'user_id' => $updater->getId()
            ]);
            throw new ApplicationAccessDeniedException('Только Админ или Партнер банка могут менять статус.');
        }

        // 2. (Опционально) Здесь можно добавить сложную "машину состояний"
        // (e.g., нельзя перевести из 'draft' в 'completed')

        // 3. Обновляем статус
        $oldStatus = $application->getStatus();
        $application->setStatus($dto->status);

        // 4. Если пришла оферта, сохраняем ее
        if ($dto->status === 'offer_received') {
            if ($dto->tariff_rate !== null) {
                $application->setTariffRate($dto->tariff_rate);
            }
            if ($dto->commission_amount !== null) {
                // Преобразуем float в string для decimal
                $application->setCommissionAmount((string)$dto->commission_amount);
            }
            // $application->setOfferData($dto->offer_data); // Если нужно доп. инфо
        }

        // 5. Если пришел отказ, сохраняем причину (в product_data)
        if ($dto->status === 'rejected' && $dto->rejection_reason) {
            $productData = $application->getProductData();
            $productData['rejection_reason'] = $dto->rejection_reason;
            $application->setProductData($productData);
        }

        $this->applicationRepository->save($application, true); // (flush: true)

        $this->logger->info('Статус заявки обновлен', [
            'app_id' => $application->getId(),
            'updater_id' => $updater->getId(),
            'old_status' => $oldStatus,
            'new_status' => $dto->status,
        ]);

        // --- [РЕАЛИЗАЦИЯ ПРОБЕЛА] ---
        // "Кричим" в очередь, чтобы уведомить Клиента/Агента
        $this->bus->dispatch(new ApplicationStatusChangedEvent(
            $application->getId(),
            $oldStatus,
            $dto->status
        ));
        // --- КОНЕЦ ---

        return $application;
    }
}
