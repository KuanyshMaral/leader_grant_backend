<?php
// src/User/Service/UserService.php

namespace App\User\Service;

use App\Document\Repository\DocumentRepository;
use App\User\DTO\UpdateCompanyProfileDTO;
use App\User\DTO\UpdateUserProfileDTO;
use App\User\Entity\Company;
use App\User\Entity\User;
use App\User\Exception\AccreditationException;
use App\User\Exception\UserAlreadyExistsException;
use App\User\Repository\CompanyRepository;
use App\User\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use App\User\Event\AccreditationSubmittedEvent;
use App\User\Event\AccreditationApprovedEvent;
use App\User\Event\AccreditationRejectedEvent;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly LoggerInterface $logger,
        private readonly DocumentRepository $documentRepository,
        private readonly MessageBusInterface $bus
    ) {
    }

    /**
     * Создает или обновляет профиль Компании для пользователя (шаг аккредитации).
     */
    public function updateCompanyProfile(User $user, UpdateCompanyProfileDTO $dto): Company
    {
        $company = $user->getCompany() ?? new Company();
        $company->setUser($user);

        // Простые поля
        $company->setName($dto->name);
        $company->setFullName($dto->full_name);
        $company->setInn($dto->inn);
        $company->setOgrn($dto->ogrn);
        $company->setKpp($dto->kpp);
        $company->setLegalAddress($dto->legal_address);
        $company->setActualAddress($dto->actual_address);
        $company->setCeoFio($dto->ceo_fio ?? '');
        $company->setTaxSystem($dto->tax_system);

        $company->setWebSite($dto->web_site);
        $company->setOfficePhone($dto->office_phone);
        $company->setVatRate($dto->vat_rate);
        $company->setAuthorizedCapital($dto->authorized_capital);
        $company->setPaidCapital($dto->paid_capital);
        $company->setEmployeeCount($dto->employee_count);
        $company->setContractCount($dto->contract_count);

        if ($dto->registration_date) {
            try {
                $company->setRegistrationDate(new \DateTimeImmutable($dto->registration_date));
            } catch (\Exception $e) {}
        }

        // --- МАССИВЫ (Просто сохраняем как есть) ---
        $company->setRequisites($dto->requisites);
        $company->setManagement($dto->management);
        $company->setFounders($dto->founders);
        $company->setLicenses($dto->licenses);
        $company->setContactPersons($dto->contact_persons);
        $company->setEtpAccounts($dto->etp_accounts);
        // -------------------------------------------

        $this->companyRepository->save($company, true);

        $this->logger->info('Профиль компании обновлен', [
            'user_id' => $user->getId(),
            'company_id' => $company->getId(),
        ]);

        return $company;
    }

    public function updateProfile(User $user, UpdateUserProfileDTO $dto): User
    {
        // 1. Если меняется Email, проверяем уникальность
        if ($dto->email && $dto->email !== $user->getEmail()) {
            $exists = $this->userRepository->findOneBy(['email' => $dto->email]);
            if ($exists) {
                throw new UserAlreadyExistsException(); // "Email уже занят"
            }
            $user->setEmail($dto->email);
        }

        if ($dto->fio) {
            $user->setFio($dto->fio);
        }

        if ($dto->phone) {
            $user->setPhone($dto->phone);
        }

        // 2. Обработка Аватара
        if ($dto->avatar_document_id) {
            $doc = $this->documentRepository->find($dto->avatar_document_id);
            if ($doc && $doc->getUploaderUser()->getId() === $user->getId()) {
                // Мы сохраняем ссылку на скачивание как "путь к аватару"
                // Это упростит жизнь фронтенду
                $downloadUrl = '/api/documents/' . $doc->getId() . '/download';
                $user->setAvatarPath($downloadUrl);
            }
        }

        $this->userRepository->save($user, true);

        $this->logger->info('Профиль пользователя обновлен', ['user_id' => $user->getId()]);

        return $user;
    }

    public function deleteUser(User $user): void
    {
        $userId = $user->getId();

        // Тут можно добавить проверку: "Нельзя удалить, если есть активные заявки"

        $this->userRepository->remove($user, true);

        $this->logger->info('Пользователь удалил свой аккаунт', ['user_id' => $userId]);
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
