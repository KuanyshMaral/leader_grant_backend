<?php
// src/Upload/MessageHandler/CleanupTemporaryUploadsHandler.php

namespace App\Upload\MessageHandler;

use App\Upload\Message\CleanupTemporaryUploadsMessage;
use App\Upload\Repository\UploadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CleanupTemporaryUploadsHandler
{
    public function __construct(
        private readonly UploadRepository $uploadRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(CleanupTemporaryUploadsMessage $message): void
    {
        $oneHourAgo = new \DateTimeImmutable('-1 hour');
        
        // Находим все неподтвержденные файлы старше 1 часа
        $qb = $this->uploadRepository->createQueryBuilder('u')
            ->where('u.isConfirmed = false')
            ->andWhere('u.isTemporary = true')
            ->andWhere('u.uploadedAt < :oneHourAgo')
            ->andWhere('u.deleted = false')
            ->setParameter('oneHourAgo', $oneHourAgo);

        $files = $qb->getQuery()->getResult();
        $count = count($files);

        if ($count === 0) {
            $this->logger->info('No temporary uploads to clean up');
            return;
        }

        foreach ($files as $file) {
            // Мягкое удаление
            $file->setDeleted(true);
            
            $this->logger->info('Temporary upload cleaned up', [
                'file_id' => $file->getId(),
                'file_name' => $file->getOriginalFileName(),
                'uploaded_at' => $file->getUploadedAt()->format('Y-m-d H:i:s'),
                'user_id' => $file->getUploadedBy()->getId()
            ]);
        }

        $this->entityManager->flush();
        
        $this->logger->info("Cleaned up {$count} temporary uploads");
    }
}
