<?php
// src/User/Api/SettingsController.php

namespace App\User\Api;

use App\User\DTO\ChangePasswordDTO;
use App\User\Entity\User;
use App\User\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/settings')]
class SettingsController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    /**
     * Смена пароля.
     */
    #[Route('/password', methods: ['POST'])]
    public function changePassword(
        #[CurrentUser] User $user,
        #[MapRequestPayload] ChangePasswordDTO $dto
    ): JsonResponse {
        if (!$this->passwordHasher->isPasswordValid($user, $dto->oldPassword)) {
            return $this->json(['error' => 'Старый пароль неверен'], 400);
        }

        $newHashedPassword = $this->passwordHasher->hashPassword($user, $dto->newPassword);
        $user->setPasswordHash($newHashedPassword);

        $this->userRepository->save($user, true);

        return $this->json(['message' => 'Пароль успешно изменен']);
    }

    /**
     * Обновление настроек уведомлений (JSON).
     */
    #[Route('/preferences', methods: ['PATCH'])]
    public function updatePreferences(
        #[CurrentUser] User $user,
        Request $request
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Мержим старые настройки с новыми
        $current = $user->getPreferences();
        $updated = array_merge($current, $data);

        $user->setPreferences($updated);
        $this->userRepository->save($user, true);

        return $this->json($user->getPreferences());
    }

    /**
     * Обновление личного профиля (ФИО, Пол, Часовой пояс).
     */
    #[Route('/profile', methods: ['PATCH'])]
    public function updateProfile(
        #[CurrentUser] User $user,
        #[MapRequestPayload] UpdateUserProfileDTO $dto
    ): JsonResponse {
        if ($dto->fio) $user->setFio($dto->fio);
        if ($dto->phone) $user->setPhone($dto->phone);
        if ($dto->gender) $user->setGender($dto->gender);
        if ($dto->timezone) $user->setTimezone($dto->timezone);

        $this->userRepository->save($user, true);

        return $this->json(['message' => 'Профиль обновлен']);
    }

    /**
     * Получение реферальной информации.
     */
    #[Route('/referral', methods: ['GET'])]
    public function getReferralData(#[CurrentUser] User $user): JsonResponse
    {
        // Генерируем ссылку на фронтенд.
        // ID пользователя = его реферальный код.
        $link = 'https://lk.leader-group.ru/register?ref=' . $user->getId();

        return $this->json([
            'referral_code' => $user->getId(),
            'referral_link' => $link,
            'description' => 'За клиентов, пришедших по ссылке, вы получите вознаграждение.'
        ]);
    }
}
