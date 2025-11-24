<?php
// src/User/Api/ProfileController.php

namespace App\User\Api;

use App\User\DTO\UpdateCompanyProfileDTO;
use App\User\Entity\User;
use App\User\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\User\DTO\UpdateUserProfileDTO;

// Все роуты в этом классе будут защищены (начинаются с /api/profile)
#[Route('/api/profile')]
class ProfileController extends AbstractController
{
    /**
     * Получает информацию о текущем авторизованном пользователе.
     */
    #[Route('/me', methods: ['GET'])]
    public function getMe(#[CurrentUser] ?User $user): JsonResponse
    {
        // #[CurrentUser] - это "магия" Symfony,
        // которая достает User'а из JWT-токена.

        if (!$user) {
            throw new AccessDeniedException('Необходима аутентификация');
        }

        // Возвращаем "безопасные" данные
        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'fio' => $user->getFio(),
            'phone' => $user->getPhone(),
            'role' => $user->getRole(),
            'status' => $user->getStatus(),
            'company_inn' => $user->getCompany()?->getInn(),
            'company_name' => $user->getCompany()?->getName(),
            'avatar' => $user->getAvatarPath(),
        ]);
    }

    /**
     * Эндпоинт для заполнения профиля компании (Аккредитация, Шаг 1).
     */
    #[Route('/company', methods: ['POST'])]
    public function updateCompanyProfile(
        #[MapRequestPayload] UpdateCompanyProfileDTO $dto,
        #[CurrentUser] ?User $user,
        UserService $userService
    ): JsonResponse {
        if (!$user) throw new AccessDeniedException();
        $company = $userService->updateCompanyProfile($user, $dto);
        return $this->json(['message' => 'Профиль компании успешно обновлен', 'company_id' => $company->getId()]);
    }

    /**
     * Обновление личных данных (ФИО, email, телефон, аватар).
     */
    #[Route('/update', methods: ['PATCH'])]
    public function updateProfile(
        #[MapRequestPayload] UpdateUserProfileDTO $dto,
        #[CurrentUser] User $user,
        UserService $userService
    ): JsonResponse {

        $updatedUser = $userService->updateProfile($user, $dto);

        return $this->json([
            'message' => 'Профиль обновлен',
            'user' => [
                'email' => $updatedUser->getEmail(),
                'fio' => $updatedUser->getFio(),
                'avatar' => $updatedUser->getAvatarPath()
            ]
        ]);
    }

    /**
     * Удаление аккаунта.
     */
    #[Route('', methods: ['DELETE'])]
    public function deleteProfile(
        #[CurrentUser] User $user,
        UserService $userService
    ): JsonResponse {

        $userService->deleteUser($user);

        return $this->json(['message' => 'Аккаунт успешно удален']);
    }

    /**
     * Эндпоинт для "подачи на аккредитацию" (Аккредитация, Шаг 2).
     */
    #[Route('/submit-accreditation', methods: ['POST'])]
    public function submitAccreditation(
        #[CurrentUser] ?User $user,
        UserService $userService
    ): JsonResponse {

        if (!$user) {
            throw new AccessDeniedException();
        }

        // Вся логика (проверки, смена статуса, "крик" в очередь)
        // спрятана внутри UserService.
        $userService->submitForAccreditation($user);

        return $this->json([
            'message' => 'Заявка на аккредитацию успешно подана',
            'new_status' => $user->getStatus(),
        ]);
    }

    /**
     * Получить ПОЛНУЮ анкету компании текущего пользователя.
     */
    #[Route('/company', methods: ['GET'])]
    public function getCompanyProfile(#[CurrentUser] User $user): JsonResponse
    {
        $company = $user->getCompany();
        if (!$company) {
            return $this->json(['error' => 'Компания не создана'], 404);
        }

        // Возвращаем объект целиком (сериализатор сам превратит его в JSON)
        // Убедитесь, что у полей в Company.php стоит группа #[Groups(['company:read'])] или ['app:read']
        return $this->json($company, 200, [], ['groups' => 'app:read']);
    }
}
