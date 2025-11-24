<?php
// src/Agent/Service/AgentContractService.php

namespace App\Agent\Service;

use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Сервис для работы с агентскими договорами и вознаграждениями.
 */
class AgentContractService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Получить условия вознаграждения для агента.
     */
    public function getAgentRewards(User $agent): array
    {
        $this->logger->debug('Fetching agent rewards', ['agent_id' => $agent->getId()]);
        
        // TODO: В будущем это должно браться из отдельной таблицы agent_rewards
        $rewards = [
            [
                'bank' => 'АК Барс',
                'service' => 'Получение Банковской гарантии',
                'reward' => '15% от комиссии, уплаченной на банк при сумме продукта до 50 000 000 р.',
                'date_start' => null,
                'date_end' => null
            ],
            [
                'bank' => 'Абсолют',
                'service' => 'Получение Банковской гарантии',
                'reward' => '20% от комиссии, уплаченной на банк, +50% от превышения тарифа',
                'date_start' => '2025-07-27',
                'date_end' => null
            ],
            [
                'bank' => 'Альфа-Банк',
                'service' => 'Получение Банковской гарантии',
                'reward' => '25% от комиссии, уплаченной на банк',
                'date_start' => null,
                'date_end' => null
            ],
            [
                'bank' => 'Сбербанк',
                'service' => 'Получение Банковской гарантии',
                'reward' => '10% от комиссии (фиксировано)',
                'date_start' => '2024-02-15',
                'date_end' => null
            ],
        ];

        $this->logger->info('Agent rewards fetched', [
            'agent_id' => $agent->getId(),
            'count' => count($rewards)
        ]);

        return $rewards;
    }

    /**
     * Получить документы агентского договора.
     */
    public function getContractDocuments(User $agent): array
    {
        $this->logger->debug('Fetching contract documents', ['agent_id' => $agent->getId()]);
        
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('u')
            ->from('App\\Upload\\Entity\\UploadedFile', 'u')
            ->where('u.uploadedBy = :agent')
            ->andWhere('u.context = :context')
            ->andWhere('u.isDeleted = false')
            ->andWhere('u.isConfirmed = true')
            ->setParameter('agent', $agent)
            ->setParameter('context', 'agent_contract')
            ->orderBy('u.uploadedAt', 'DESC');

        $files = $qb->getQuery()->getResult();

        $documents = [];
        foreach ($files as $file) {
            $documents[] = [
                'id' => $file->getId(),
                'title' => $file->getDescription() ?: $file->getOriginalFileName(),
                'file_name' => $file->getOriginalFileName(),
                'url' => '/uploads/' . $file->getStoredFileName(),
                'date' => $file->getUploadedAt()->format('d.m.Y')
            ];
        }

        // Если нет загруженных документов, возвращаем дефолтные
        if (empty($documents)) {
            $this->logger->info('No contract documents found, returning defaults', ['agent_id' => $agent->getId()]);
            
            $documents = [
                [
                    'id' => null,
                    'title' => 'Заявление о присоединении к регламенту',
                    'file_name' => 'statement.pdf',
                    'url' => null,
                    'date' => '21.03.2025'
                ],
                [
                    'id' => null,
                    'title' => 'Согласие на обработку персональных данных',
                    'file_name' => 'consent.pdf',
                    'url' => null,
                    'date' => '21.03.2025'
                ],
                [
                    'id' => null,
                    'title' => 'Лист записи / Скан свидетельства ОГРНИП',
                    'file_name' => 'ogrn.pdf',
                    'url' => null,
                    'date' => '21.03.2025'
                ],
                [
                    'id' => null,
                    'title' => 'Агентский договор',
                    'file_name' => 'contract.pdf',
                    'url' => null,
                    'date' => '07.03.2025'
                ],
            ];
        } else {
            $this->logger->info('Contract documents fetched', [
                'agent_id' => $agent->getId(),
                'count' => count($documents)
            ]);
        }

        return $documents;
    }
}
