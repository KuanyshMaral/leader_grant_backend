<?php

namespace App\CallBase\Service;

use App\CallBase\Repository\LeadRepository;
use App\CallBase\Entity\Lead;
use App\User\Repository\UserRepository;
use App\Application\Service\ApplicationService;
use App\Application\DTO\CreateApplicationDTO;
use App\Bank\Repository\BankRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class LeadService
{
    public function __construct(
        private readonly LeadRepository $leadRepo,
        private readonly UserRepository $userRepo,
        private readonly BankRepository $bankRepo,
        private readonly ApplicationService $appService,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger
    ) {}
    
    /**
     * Get leads for agent (formatted for frontend)
     */
    public function getLeadsForAgent(int $agentUserId): array
    {
        $leads = $this->leadRepo->findForAgent($agentUserId);
        
        return array_map(fn($lead) => [
            'id' => $lead->getId(),
            'name' => $lead->getCompanyName(),
            'inn' => $lead->getInn(),
            'contact' => $lead->getContactPerson() ?? 'Не указан',
            'phone' => $lead->getPhone() ?? '',
            'status' => $lead->getStatus(),
            'comment' => $lead->getComment() ?? '',
            'converted' => $lead->isConverted(),
            'application_id' => $lead->getConvertedToApplication()?->getId(),
        ], $leads);
    }
    
    /**
     * Update lead status
     */
    public function updateStatus(int $leadId, string $status): void
    {
        $lead = $this->leadRepo->find($leadId);
        if (!$lead) {
            throw new \Exception('Lead not found');
        }
        
        $allowedStatuses = ['new', 'process', 'success', 'rejected'];
        if (!in_array($status, $allowedStatuses)) {
            throw new \InvalidArgumentException("Invalid status: $status");
        }
        
        $lead->setStatus($status);
        $this->em->flush();
        
        $this->logger->info('Lead status updated', [
            'lead_id' => $leadId,
            'status' => $status
        ]);
    }
    
    /**
     * Update lead comment
     */
    public function updateComment(int $leadId, string $comment): void
    {
        $lead = $this->leadRepo->find($leadId);
        if (!$lead) {
            throw new \Exception('Lead not found');
        }
        
        $lead->setComment($comment);
        $this->em->flush();
        
        $this->logger->info('Lead comment updated', [
            'lead_id' => $leadId,
            'comment_length' => strlen($comment)
        ]);
    }
    
    /**
     * Convert successful lead to Application + Client
     */
    public function convertLeadToApplication(
        int $leadId, 
        int $agentUserId,
        array $applicationData
    ): int {
        $lead = $this->leadRepo->find($leadId);
        if (!$lead) {
            throw new \Exception('Lead not found');
        }
        
        if ($lead->isConverted()) {
            throw new \Exception('Lead already converted to application');
        }
        
        $agent = $this->userRepo->find($agentUserId);
        if (!$agent) {
            throw new \Exception('Agent not found');
        }
        
        // 1. Get or create client user
        $client = $this->getOrCreateClient($lead);
        
        // 2. Get first available bank (or use default)
        $banks = $this->bankRepo->findAll();
        if (empty($banks)) {
            throw new \Exception('No banks available');
        }
        $defaultBank = $banks[0];
        
        // 3. Create application using ApplicationService
        $dto = new CreateApplicationDTO(
            client_user_id: $client->getId(),
            agent_user_id: $agentUserId,
            bank_id: $defaultBank->getId(),
            product_type: $applicationData['product_type'] ?? 'bank_guarantee',
            amount: (float)($applicationData['amount'] ?? 0),
            term_days: (int)($applicationData['term_days'] ?? 365),
            product_data: $applicationData['product_data'] ?? []
        );
        
        $applications = $this->appService->createApplications($dto, $agent);
        $application = $applications[0];
        
        // 4. Link lead to application and client
        $lead->setConvertedToApplication($application);
        $lead->setConvertedToClient($client);
        $lead->setConvertedAt(new \DateTime());
        $lead->setStatus('success');
        
        $this->em->flush();
        
        $this->logger->info('Lead converted to application', [
            'lead_id' => $leadId,
            'application_id' => $application->getId(),
            'client_id' => $client->getId(),
        ]);
        
        return $application->getId();
    }
    
    /**
     * Get or create client from lead
     */
    private function getOrCreateClient(Lead $lead): \App\User\Entity\User
    {
        // Check if client already exists by company INN
        $existingClient = $this->userRepo->createQueryBuilder('u')
            ->leftJoin('u.company', 'c')
            ->where('c.inn = :inn')
            ->setParameter('inn', $lead->getInn())
            ->getQuery()
            ->getOneOrNullResult();
        
        if ($existingClient) {
            return $existingClient;
        }
        
        // Create new client user
        $client = new \App\User\Entity\User();
        $client->setEmail($lead->getEmail() ?? $this->generateEmail($lead->getInn()));
        $client->setPasswordHash(password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT));
        $client->setRole('client');
        $client->setStatus('active');
        $client->setFio($lead->getContactPerson() ?? 'Клиент');
        $client->setPhone($lead->getPhone() ?? '');
        
        $this->em->persist($client);
        
        // Create company for the client
        $company = new \App\User\Entity\Company();
        $company->setUser($client);
        $company->setName($lead->getCompanyName());
        $company->setFullName($lead->getCompanyName());
        $company->setInn($lead->getInn());
        
        $this->em->persist($company);
        $this->em->flush();
        
        $this->logger->info('Created new client from lead', [
            'lead_id' => $lead->getId(),
            'client_id' => $client->getId(),
            'inn' => $lead->getInn()
        ]);
        
        return $client;
    }
    
    private function generateEmail(string $inn): string
    {
        return "client_{$inn}@temp.leadergrant.ru";
    }
}
