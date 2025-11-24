<?php
// src/User/Api/AdminUserController.php

namespace App\User\Api;

use App\User\Entity\User;
use App\User\DTO\CreatePartnerDTO;
use App\User\Repository\UserRepository;
use App\User\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use App\Bank\Repository\BankRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api/admin/users')]
#[IsGranted('ROLE_ADMIN')] // <-- ВЕСЬ КОНТРОЛЛЕР ЗАЩИЩЕН
class AdminUserController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly UserRepository $userRepository
    ) {
    }

    /**
     * [Админ] Получает "Очередь на аккредитацию".
     * (Тех, у кого статус 'pending_review')
     */
    #[Route('/accreditation/pending', methods: ['GET'])]
    public function getPendingAccreditations(): JsonResponse
    {
        // (Этот кастомный метод мы уже реализовали в UserRepository)
        $users = $this->userRepository->findPendingAccreditation();

        // Мы не можем вернуть User целиком (из-за пароля),
        // поэтому "очищаем" его вручную.
        $result = array_map(function (User $user) {
            return [
                'id' => $user->getId(),
                'fio' => $user->getFio(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
                'company_name' => $user->getCompany()?->getName(),
                'inn' => $user->getCompany()?->getInn(),
            ];
        }, $users);

        return $this->json($result);
    }

    /**
     * [Админ] Одобряет аккредитацию.
     */
    #[Route('/{id}/approve-accreditation', methods: ['POST'])]
    public function approve(int $id, #[CurrentUser] User $admin): JsonResponse
    {
        $userToApprove = $this->userRepository->find($id);
        if (!$userToApprove) {
            return $this->json(['error' => 'Пользователь не найден'], 404);
        }

        // Вызываем сервис (он сам "крикнет" событие AccreditationApprovedEvent)
        $this->userService->approveAccreditation($userToApprove);

        return $this->json([
            'message' => 'Аккредитация одобрена',
            'new_status' => $userToApprove->getStatus(), // (будет 'active')
        ]);
    }

    /**
     * [Админ] Отклоняет аккредитацию.
     */
    #[Route('/{id}/reject-accreditation', methods: ['POST'])]
    public function reject(int $id, Request $request, #[CurrentUser] User $admin): JsonResponse
    {
        $userToReject = $this->userRepository->find($id);
        if (!$userToReject) {
            return $this->json(['error' => 'Пользователь не найден'], 404);
        }

        // Получаем причину из JSON-тела: {"reason": "..."}
        $data = $request->toArray();
        $reason = $data['reason'] ?? 'Причина не указана';

        // Вызываем сервис (он сам "крикнет" событие AccreditationRejectedEvent)
        $this->userService->rejectAccreditation($userToReject, $reason);

        return $this->json([
            'message' => 'Аккредитация отклонена',
            'new_status' => $userToReject->getStatus(), // (будет 'rejected')
        ]);
    }

    /**
     * [Админ] Создает Партнера (сотрудника банка).
     */
    #[Route('/create-partner', methods: ['POST'])]
    public function createPartner(
        #[MapRequestPayload] CreatePartnerDTO $dto,
        UserPasswordHasherInterface $passwordHasher,
        BankRepository $bankRepository
    ): JsonResponse {

        // 1. Проверяем, есть ли такой банк
        $bank = $bankRepository->find($dto->bank_id);
        if (!$bank) {
            return $this->json(['error' => 'Банк не найден'], 404);
        }

        // 2. Проверяем email
        if ($this->userRepository->findOneBy(['email' => $dto->email])) {
            return $this->json(['error' => 'Email уже занят'], 409);
        }

        // 3. Создаем User
        $user = new User();
        $user->setEmail($dto->email);
        $user->setFio($dto->fio);
        $user->setPhone($dto->phone);
        $user->setRole('partner');
        $user->setStatus('active'); // Партнеру не нужна аккредитация
        $user->setBank($bank);      // <--- ПРИВЯЗЫВАЕМ К БАНКУ!

        // 4. Пароль
        $hashedPwd = $passwordHasher->hashPassword($user, $dto->password);
        $user->setPasswordHash($hashedPwd);

        $this->userRepository->save($user, true);

        return $this->json([
            'message' => 'Партнер успешно создан',
            'id' => $user->getId()
        ], 201);
    }

    #[Route('/{id}/assign-manager', methods: ['PATCH'])]
    public function assignManager(int $id, Request $request): JsonResponse
    {
        $client = $this->userRepository->find($id);
        if (!$client) return $this->json(['error' => 'Пользователь не найден'], 404);
        $data = $request->toArray();
        $managerId = $data['manager_id'] ?? null;
        if (!$managerId) return $this->json(['error' => 'manager_id обязателен'], 400);
        $manager = $this->userRepository->find($managerId);
        if (!$manager || $manager->getRole() !== 'admin') return $this->json(['error' => 'Менеджер не найден'], 404);
        $client->setPersonalManager($manager);
        $this->userRepository->save($client, true);
        return $this->json(['message' => 'Менеджер назначен', 'manager_id' => $manager->getId()]);
    }

    #[Route('/{id}/remove-manager', methods: ['DELETE'])]
    public function removeManager(int $id): JsonResponse
    {
        $client = $this->userRepository->find($id);
        if (!$client) return $this->json(['error' => 'Пользователь не найден'], 404);
        $client->setPersonalManager(null);
        $this->userRepository->save($client, true);
        return $this->json(['message' => 'Менеджер удален']);
    }

    #[Route('/managers', methods: ['GET'])]
    public function listManagers(): JsonResponse
    {
        $managers = $this->userRepository->findBy(['role' => 'admin']);
        $result = array_map(fn($m) => ['id' => $m->getId(), 'fio' => $m->getFio(), 'email' => $m->getEmail()], $managers);
        return $this->json($result);
    }
}
