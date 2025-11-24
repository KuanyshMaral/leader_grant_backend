<?php
// src/User/Api/AdminCompanyController.php

namespace App\User\Api;

use App\User\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/companies')]
#[IsGranted('ROLE_ADMIN')]
class AdminCompanyController extends AbstractController
{
    public function __construct(
        private readonly CompanyRepository $companyRepository
    ) {
    }

    /**
     * Получить список всех компаний (для админа).
     * GET /api/admin/companies
     */
    #[Route('', methods: ['GET'])]
    public function listAll(): JsonResponse
    {
        $companies = $this->companyRepository->findAll();

        return $this->json($companies, 200, [], ['groups' => 'company:read']);
    }
}
