<?php
// src/User/Service/AgentService.php

namespace App\User\Service;

use App\User\DTO\AddClientDTO; // <--- ВОТ ЭТОЙ СТРОКИ НЕ ХВАТАЕТ ИЛИ ОНА БЫЛА УДАЛЕНА
use App\User\Entity\ClientAgentLink;
use App\User\Entity\Company;
use App\User\Entity\User;
use App\User\Repository\ClientAgentLinkRepository;
use App\User\Repository\CompanyRepository;
use App\User\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Document\Repository\DocumentRepository;

class AgentService
{
    public function __construct(
        private readonly ClientAgentLinkRepository $linkRepository,
        private readonly UserRepository $userRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly DocumentRepository $documentRepository
    ) {}

    public function getMyClients(User $agent): array
    {
        $links = $this->linkRepository->findClientsByAgent($agent->getId());
        $result = [];
        foreach ($links as $link) {
            $client = $link->getClientUser();
            $company = $client->getCompany();
            $result[] = [
                'id' => $client->getId(),
                'fio' => $client->getFio(),
                'email' => $client->getEmail(),
                'phone' => $client->getPhone(),
                'status' => $client->getStatus(),
                'company_name' => $company ? $company->getName() : 'Без компании',
                'inn' => $company ? $company->getInn() : '-',
            ];
        }
        return $result;
    }

    /**
     * Агент добавляет нового клиента.
     */
    public function addClient(User $agent, AddClientDTO $dto): void
    {
        // 1. Ищем, есть ли уже такой пользователь
        $client = $this->userRepository->findOneBy(['email' => $dto->email]);

        if (!$client) {
            // 2. Если нет - создаем нового (User-заглушка)
            $client = new User();
            $client->setEmail($dto->email);
            $client->setFio($dto->fio);
            $client->setPhone($dto->phone ?? '');
            $client->setRole('client');
            $client->setStatus('pending_invite'); // Ждет приглашения

            // Генерируем случайный пароль
            $randomPass = bin2hex(random_bytes(8));
            $client->setPasswordHash($this->passwordHasher->hashPassword($client, $randomPass));

            $this->userRepository->save($client); // Сохраняем
        }

        // 3. Если передан ИНН, создаем компанию
        if ($dto->inn && !$client->getCompany()) {
            $company = new Company();
            $company->setUser($client);
            $company->setInn($dto->inn);
            $company->setName('Новая компания');
            $company->setFullName('Новая компания');
            $company->setLegalAddress('-');
            $company->setOgrn('-');
            $company->setCeoFio('-');
            $company->setTaxSystem('OSN');
            $this->companyRepository->save($company);
        }

        // 4. Создаем СВЯЗЬ (Агент <-> Клиент)
        $existingLink = $this->linkRepository->findOneBy([
            'agent_user' => $agent,
            'client_user' => $client
        ]);

        if (!$existingLink) {
            $link = new ClientAgentLink();
            $link->setAgentUser($agent);
            $link->setClientUser($client);
            $link->setStatus('linked');
            $this->linkRepository->save($link, true); // flush
        }
    }

    /**
     * Проверяет связь и возвращает Клиента.
     */
    public function getClient(User $agent, int $clientId): User
    {
        $link = $this->linkRepository->findOneBy([
            'agent_user' => $agent,
            'client_user' => $clientId,
            'status' => 'linked'
        ]);

        if (!$link) {
            throw new \Exception('Клиент не найден или нет доступа'); // (Лучше кастомный Exception)
        }

        return $link->getClientUser();
    }

    /**
     * Получает документы клиента.
     */
    public function getClientDocuments(User $agent, int $clientId): array
    {
        $client = $this->getClient($agent, $clientId); // Проверка доступа
        // Используем репозиторий документов (нужно добавить его в __construct)
        // (Предполагается, что вы добавили DocumentRepository в конструктор этого сервиса)
        return $this->documentRepository->findAllByUser($client->getId());
    }
}
