<?php
// src/User/Api/UserController.php

namespace App\User\Api;

use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api/user', name: 'user_')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    /**
     * Обновить профиль пользователя.
     * 
     * PATCH /api/user/profile
     */
    #[Route('/profile', name: 'update_profile', methods: ['PATCH'])]
    public function updateProfile(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['fio'])) {
            $user->setFio($data['fio']);
        }

        if (isset($data['phone'])) {
            $user->setPhone($data['phone']);
        }

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        if (isset($data['gender'])) {
            $user->setGender($data['gender']);
        }

        if (isset($data['timezone'])) {
            $user->setTimezone($data['timezone']);
        }

        // Обновление URL аватара
        if (isset($data['avatar_url'])) {
            $user->setAvatarPath($data['avatar_url']);
            
            // Автоподтверждение файла если передан file_id
            if (isset($data['avatar_file_id'])) {
                $uploadedFile = $this->entityManager->getRepository(\App\Upload\Entity\UploadedFile::class)
                    ->find($data['avatar_file_id']);
                    
                if ($uploadedFile && $uploadedFile->getUploadedBy()->getId() === $user->getId()) {
                    $uploadedFile->setConfirmed(true);
                }
            }
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Профиль обновлён',
            'user' => [
                'id' => $user->getId(),
                'fio' => $user->getFio(),
                'email' => $user->getEmail(),
                'avatar' => $user->getAvatar()
            ]
        ]);
    }

    /**
     * Изменить пароль.
     * 
     * POST /api/user/password
     */
    #[Route('/password', name: 'change_password', methods: ['POST'])]
    public function changePassword(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $oldPassword = $data['oldPassword'] ?? null;
        $newPassword = $data['newPassword'] ?? null;

        if (!$oldPassword || !$newPassword) {
            return new JsonResponse(['error' => 'Не указан старый или новый пароль'], 400);
        }

        // Проверка старого пароля
        if (!$this->passwordHasher->isPasswordValid($user, $oldPassword)) {
            return new JsonResponse(['error' => 'Неверный текущий пароль'], 400);
        }

        // Установка нового пароля
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Пароль успешно изменён']);
    }

    /**
     * Обновить настройки уведомлений.
     * 
     * PATCH /api/user/preferences
     */
    #[Route('/preferences', name: 'update_preferences', methods: ['PATCH'])]
    public function updatePreferences(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // TODO: Сохранить preferences в базу или в redis
        // Пока просто возвращаем успех

        return new JsonResponse(['message' => 'Настройки обновлены', 'preferences' => $data]);
    }

    /**
     * Удалить аккаунт.
     * 
     * DELETE /api/user/profile
     */
    #[Route('/profile', name: 'delete_profile', methods: ['DELETE'])]
    public function deleteProfile(#[CurrentUser] User $user): JsonResponse
    {
        // Мягкое удаление или полное - решать вам
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Аккаунт удалён']);
    }
}
