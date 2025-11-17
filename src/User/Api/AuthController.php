<?php
// src/User/Api/AuthController.php

namespace App\User\Api;

use App\User\DTO\RegisterUserDTO;
use App\User\Service\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    /**
     * Эндпоинт для регистрации нового пользователя.
     */
    #[Route('/api/register', methods: ['POST'])]
    public function register(
        #[MapRequestPayload] RegisterUserDTO $dto, // <--- Авто-валидация DTO
        RegistrationService $registrationService
    ): JsonResponse {

        // Вся "грязная" работа (хеширование, проверка email)
        // спрятана внутри RegistrationService
        $user = $registrationService->register($dto);

        return $this->json([
            'message' => 'Пользователь успешно зарегистрирован',
            'userId' => $user->getId(),
            'email' => $user->getEmail()
        ], 201); // 201 Created
    }

    /**
     * Эндпоинт для входа в систему (получения JWT-токена).
     */
    #[Route('/api/login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // [!] ВАЖНО:
        // Тело этого метода НИКОГДА не выполнится.
        // Запрос будет "перехвачен" файрволом 'login' (из security.yaml),
        // который мы настроили выше.
        // LexikJwtAuthenticationBundle сам проверит 'email' и 'password',
        // и вернет JSON с "token", если все в порядке.

        throw new \LogicException('Этот метод не должен вызываться. Проверьте security.yaml');
    }
}
