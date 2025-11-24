<?php
// src/User/Service/AgentService.php

namespace App\User\Service;

use App\User\DTO\AddClientDTO;
use App\User\Entity\ClientAgentLink;
use App\User\Entity\Company;
use App\User\Entity\User;
use App\User\Repository\ClientAgentLinkRepository;
use App\User\Repository\CompanyRepository;
use App\User\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Document\Repository\DocumentRepository;
use Psr\Log\LoggerInterface;

class AgentService
{
    public function __construct(
        private readonly ClientAgentLinkRepository $linkRepository,
        private readonly UserRepository $userRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly DocumentRepository $documentRepository,
        private readonly \App\Document\Service\DocumentService $documentService,
        private readonly LoggerInterface $logger
    ) {}

    // ... (existing methods)


    /**
     * Получить список моих клиентов.
     */
    public function getMyClients(User $agent): array
    {
        $this->logger->debug('Fetching clients for agent', [
            'agent_id' => $agent->getId()
        ]);
        
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
        
        $this->logger->info('Clients fetched for agent', [
            'agent_id' => $agent->getId(),
            'count' => count($result)
        ]);
        
        return $result;
    }

    /**
     * Агент добавляет нового клиента.
     */
    public function addClient(User $agent, AddClientDTO $dto): void
    {
        $this->logger->info('Agent adding new client', [
            'agent_id' => $agent->getId(),
            'client_email' => $dto->email
        ]);
        
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
            
            $this->logger->info('New client user created', [
                'client_id' => $client->getId(),
                'email' => $dto->email
            ]);
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
            
            $this->logger->info('Company created for client', [
                'client_id' => $client->getId(),
                'inn' => $dto->inn
            ]);
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
            
            $this->logger->info('Client linked to agent', [
                'agent_id' => $agent->getId(),
                'client_id' => $client->getId()
            ]);
        } else {
            $this->logger->debug('Client already linked to agent', [
                'agent_id' => $agent->getId(),
                'client_id' => $client->getId()
            ]);
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
        return $this->documentRepository->findAllByClient($client);
    }

    /**
     * Агент загружает документ для клиента.
     */
    public function uploadClientDocument(User $agent, int $clientId, \Symfony\Component\HttpFoundation\File\UploadedFile $file, string $docType): \App\Document\Entity\Document
    {
        $client = $this->getClient($agent, $clientId);
        
        // 1. Загружаем файл (как обычно)
        $document = $this->documentService->uploadFile($file, $agent, $docType);

        // 2. Привязываем к компании клиента
        if ($client->getCompany()) {
            $document->setCompany($client->getCompany());
        }

        $this->documentRepository->save($document, true);

        return $document;
    }
}
