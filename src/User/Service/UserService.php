<?php
// src/User/Service/UserService.php

namespace App\User\Service;

use App\User\DTO\UpdateCompanyProfileDTO;
use App\User\Entity\Company;
use App\User\Entity\User;
use App\User\Exception\AccreditationException;
use App\User\Repository\CompanyRepository;
use App\User\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use App\User\Event\AccreditationSubmittedEvent;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $bus
    ) {
    }

    /**
     * Создает или обновляет профиль Компании для пользователя (шаг аккредитации).
     */
    public function updateCompanyProfile(User $user, UpdateCompanyProfileDTO $dto): Company
    {
        // 1. Находим компанию пользователя или создаем новую
        $company = $user->getCompany() ?? new Company();
        $company->setUser($user); // Связываем с User

        // 2. Переносим данные из DTO в Entity
        $company->setName($dto->name);
        $company->setFullName($dto->full_name);
        $company->setInn($dto->inn);
        $company->setOgrn($dto->ogrn);
        $company->setLegalAddress($dto->legal_address);
        $company->setCeoFio($dto->ceo_fio);
        $company->setTaxSystem($dto->tax_system);

        // 3. Преобразуем вложенный DTO реквизитов в массив для jsonb
        $requisitesArray = [
            'bik' => $dto->requisites->bik,
            'checking_account' => $dto->requisites->checking_account,
            'corr_account' => $dto->requisites->corr_account,
        ];
        $company->setRequisites($requisitesArray);

        // 4. Сохраняем
        $this->companyRepository->save($company, true);

        $this->logger->info('Профиль компании обновлен', [
            'user_id' => $user->getId(),
            'company_id' => $company->getId(),
            'inn' => $company->getInn(),
        ]);

        return $company;
    }

    /**
     * Пользователь нажимает "Подать заявку на аккредитацию" (из PDF).
     */
    public function submitForAccreditation(User $user): void
    {
        // ... (этот метод у вас реализован правильно) ...
        if ($user->getStatus() !== 'pending_accreditation') {
            throw new AccreditationException('Неверный статус для подачи на аккредитацию.');
        }
        if ($user->getCompany() === null) {
            throw new AccreditationException('Невозможно подать на аккредитацию: профиль компании не заполнен.');
        }

        // (TODO: Проверка, что все ОБЯЗАТЕЛЬНЫЕ документы загружены)

        $user->setStatus('pending_review'); // Статус "На проверке у Админа"
        $this->userRepository->save($user, true);

        $this->logger->info('Пользователь подал на аккредитацию', [
            'user_id' => $user->getId(),
        ]);

        $this->bus->dispatch(new AccreditationSubmittedEvent(
            $user->getId(),
            $user->getCompany()->getId()
        ));
    }

    // --- [РЕАЛИЗАЦИЯ ПРОБЕЛОВ ДЛЯ АДМИНА] ---

    /**
     * [ADMIN] Одобряет аккредитацию пользователя.
     */
    public function approveAccreditation(User $userToApprove): void
    {
        if ($userToApprove->getStatus() !== 'pending_review') {
            throw new AccreditationException(
                'Нельзя одобрить пользователя со статусом: ' . $userToApprove->getStatus()
            );
        }

        $userToApprove->setStatus('active'); // <-- СТАТУС "АКТИВЕН"
        $this->userRepository->save($userToApprove, true);

        $this->logger->info('Аккредитация одобрена админом', [
            'user_id' => $userToApprove->getId(),
        ]);

        // "Кричим" в очередь, чтобы отправить email пользователю "Ваш аккаунт одобрен!"
        $this->bus->dispatch(new AccreditationApprovedEvent($userToApprove->getId()));
    }

    /**
     * [ADMIN] Отклоняет аккредитацию пользователя.
     */
    public function rejectAccreditation(User $userToReject, string $reason): void
    {
        if (!in_array($userToReject->getStatus(), ['pending_review', 'pending_accreditation'])) {
            throw new AccreditationException(
                'Нельзя отклонить пользователя со статусом: ' . $userToReject->getStatus()
            );
        }

        $userToReject->setStatus('rejected'); // <-- СТАТУС "ОТКЛОНЕН"
        $this->userRepository->save($userToReject, true);

        $this->logger->info('Аккредитация отклонена админом', [
            'user_id' => $userToReject->getId(),
            'reason' => $reason
        ]);

        // "Кричим" в очередь, чтобы отправить email "Вам отказано по причине: ..."
        $this->bus->dispatch(new AccreditationRejectedEvent($userToReject->getId(), $reason));
    }
}
