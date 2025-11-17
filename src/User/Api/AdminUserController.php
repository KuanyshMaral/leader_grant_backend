<?php
// src/User/Api/AdminUserController.php

namespace App\User\Api;

use App\User\Entity\User;
use App\User\Repository\UserRepository;
use App\User\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
}
