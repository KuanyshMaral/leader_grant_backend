<?php
// src/User/Api/CompanyController.php

namespace App\User\Api;

use App\User\DTO\UpdateCompanyDTO;
use App\User\Entity\User;
use App\User\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/companies')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class CompanyController extends AbstractController
{
    public function __construct(
        private readonly CompanyRepository $companyRepository
    ) {
    }

    /**
     * Получить данные компании.
     * GET /api/companies/{id}
     */
    #[Route('/{id}', methods: ['GET'])]
    public function getOne(int $id, #[CurrentUser] User $user): JsonResponse
    {
        $company = $this->companyRepository->find($id);

        if (!$company) {
            return $this->json(['error' => 'Компания не найдена'], 404);
        }

        // Проверяем доступ: только владелец или админ
        if ($user->getRole() !== 'admin' && $company->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Доступ запрещен'], 403);
        }

        return $this->json($company, 200, [], ['groups' => 'company:read']);
    }

    /**
     * Обновить данные компании.
     * PUT /api/companies/{id}
     */
    #[Route('/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        #[MapRequestPayload] UpdateCompanyDTO $dto,
        #[CurrentUser] User $user
    ): JsonResponse {
        $company = $this->companyRepository->find($id);

        if (!$company) {
            return $this->json(['error' => 'Компания не найдена'], 404);
        }

        // Проверяем доступ: только владелец или админ
        if ($user->getRole() !== 'admin' && $company->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Доступ запрещен'], 403);
        }

        // Обновляем поля (только если они переданы)
        if ($dto->name) $company->setName($dto->name);
        if ($dto->full_name) $company->setFullName($dto->full_name);
        if ($dto->legal_address) $company->setLegalAddress($dto->legal_address);
        if ($dto->actual_address) $company->setActualAddress($dto->actual_address);
        if ($dto->ceo_fio) $company->setCeoFio($dto->ceo_fio);
        if ($dto->tax_system) $company->setTaxSystem($dto->tax_system);
        if ($dto->employee_count !== null) $company->setEmployeeCount($dto->employee_count);
        if ($dto->contract_count !== null) $company->setContractCount($dto->contract_count);
        if ($dto->requisites) $company->setRequisites($dto->requisites);
        if ($dto->management) $company->setManagement($dto->management);
        if ($dto->founders) $company->setFounders($dto->founders);
        if ($dto->licenses) $company->setLicenses($dto->licenses);
        if ($dto->contact_persons) $company->setContactPersons($dto->contact_persons);
        if ($dto->etp_accounts) $company->setEtpAccounts($dto->etp_accounts);
        if ($dto->webSite) $company->setWebSite($dto->webSite);
        if ($dto->officePhone) $company->setOfficePhone($dto->officePhone);
        if ($dto->vatRate) $company->setVatRate($dto->vatRate);

        $this->companyRepository->save($company, true);

        return $this->json($company, 200, [], ['groups' => 'company:read']);
    }
}
