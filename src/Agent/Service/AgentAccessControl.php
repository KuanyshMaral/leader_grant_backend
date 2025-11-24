<?php
// src/Agent/Service/AgentAccessControl.php

namespace App\Agent\Service;

use App\User\Entity\User;
use App\Application\Entity\Application;

/**
 * Централизованный сервис для проверки прав доступа агентов.
 * Устраняет дублирование логики проверок доступа.
 */
class AgentAccessControl
{
    /**
     * Проверяет, может ли агент получить доступ к клиенту.
     */
    public function canAccessClient(User $agent, User $client): bool
    {
        if ($agent->getRole() !== 'agent') {
            return false;
        }
        
        // Агент может работать только со своими клиентами
        return $client->getReferrerAgent()?->getId() === $agent->getId();
    }
    
    /**
     * Проверяет, может ли агент получить доступ к заявке.
     */
    public function canAccessApplication(User $agent, Application $application): bool
    {
        if ($agent->getRole() !== 'agent') {
            return false;
        }
        
        // Агент может работать только со своими заявками
        return $application->getAgentUser()?->getId() === $agent->getId();
    }
    
    /**
     * Проверяет, может ли агент редактировать заявку.
     */
    public function canEditApplication(User $agent, Application $application): bool
    {
        if (!$this->canAccessApplication($agent, $application)) {
            return false;
        }
        
        // Нельзя редактировать терминальные статусы
        $terminalStatuses = ['rejected', 'completed', 'archived'];
        return !in_array($application->getStatus(), $terminalStatuses);
    }
    
    /**
     * Проверяет, может ли агент просматривать документы клиента.
     */
    public function canAccessClientDocuments(User $agent, User $client): bool
    {
        return $this->canAccessClient($agent, $client);
    }
    
    /**
     * Проверяет, может ли агент добавлять взаимодействия с клиентом.
     */
    public function canAddInteraction(User $agent, User $client): bool
    {
        return $this->canAccessClient($agent, $client);
    }
    
    /**
     * Выбрасывает исключение, если доступ запрещен.
     */
    public function ensureCanAccessClient(User $agent, User $client): void
    {
        if (!$this->canAccessClient($agent, $client)) {
            throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException(
                'Вы не можете получить доступ к этому клиенту'
            );
        }
    }
    
    /**
     * Выбрасывает исключение, если доступ к заявке запрещен.
     */
    public function ensureCanAccessApplication(User $agent, Application $application): void
    {
        if (!$this->canAccessApplication($agent, $application)) {
            throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException(
                'Вы не можете получить доступ к этой заявке'
            );
        }
    }
}
