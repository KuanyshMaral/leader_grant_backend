<?php
// src/Integration/Api/IntegrationController.php

namespace App\Integration\Api;

use App\Integration\Service\IntegrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;

// Все роуты здесь будут защищены JWT
#[Route('/api/integration')]
#[IsGranted('IS_AUTHENTICATED_FULLY')] // Доступно всем (Клиент, Агент, Админ)
class IntegrationController extends AbstractController
{
    public function __construct(
        private readonly IntegrationService $integrationService
    ) {
    }

    /*|*
     * Эндпоинт для автозаполнения по ИНН (checko.ru).
     *
     * GET /api/integration/inn/7707083893
     */

    #[Route('/inn/{inn}', methods: ['GET'], requirements: ['inn' => '\d{10,12}'])]
    public function getCompanyInfo(
        #[Assert\NotBlank]
        #[Assert\Length(min: 10, max: 12)]
        string $inn
    ): JsonResponse {

        // Этот эндпоинт ПОЛНОСТЬЮ РАБОЧИЙ
        // (при условии, что CHEKO_API_KEY вставлен в .env)

        $companyInfo = $this->integrationService->getCompanyInfoByInn($inn);

        return $this->json($companyInfo);
    }

    /**
     * Эндпоинт для автозаполнения по № Закупки (ЕИС).
     *
     * GET /api/integration/tender/0123456789
     */

    #[Route('/tender/{number}', methods: ['GET'])]
    public function getTenderInfo(
        #[Assert\NotBlank]
        string $number
    ): JsonResponse {

        // --- TODO (ЗАГЛУШКА API-КЛЮЧА) ---
        //
        // Этот эндпоинт ПОЛНОСТЬЮ РЕАЛИЗОВАН.
        // Он вызывает TenderApiClient, который использует
        // реальный URL и реальный парсер (из guide.txt).
        //
        // Он БУДЕТ ВЫДАВАТЬ ОШИБКУ (401 Unauthorized или 503),
        // ПОКА клиент не получит и не вставит
        // реальный EIS_API_KEY в файл .env.
        //
        // ---

        $tenderInfo = $this->integrationService->getTenderInfoByNumber($number);

        return $this->json($tenderInfo);
    }

    /**
     * Эндпоинт для автозаполнения по ИНН (MOCK EGRUL).
     * Используется в калькуляторе для быстрого тестирования.
     * 
     * GET /api/integration/company/7707083893
     */
    #[Route('/company/{inn}', methods: ['GET'], requirements: ['inn' => '\d{10,12}'])]
    public function getCompanyByInn(
        string $inn,
        \App\Integration\Service\EGRULService $egrul
    ): JsonResponse {
        try {
            $company = $egrul->getCompanyByINN($inn);
            
            return $this->json([
                'inn' => $company->inn,
                'name' => $company->name,
                'ogrn' => $company->ogrn,
                'address' => $company->legal_address,
                'status' => $company->status
            ]);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * Эндпоинт для автозаполнения по номеру закупки (MOCK EIS).
     * Используется в калькуляторе для быстрого тестирования.
     * 
     * GET /api/integration/procurement/0373100002421000123
     */
    #[Route('/procurement/{number}', methods: ['GET'])]
    public function getProcurementByNumber(
        string $number,
        \App\Integration\Service\EISService $eis
    ): JsonResponse {
        try {
            $procurement = $eis->getProcurementByNumber($number);
            
            return $this->json($procurement->toArray());
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }
}
