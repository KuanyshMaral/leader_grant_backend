<?php
// src/Upload/Command/CleanupTemporaryUploadsCommand.php

namespace App\Upload\Command;

use App\Upload\Repository\UploadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup-temporary-uploads',
    description: 'Удаляет неподтвержденные файлы старше 1 часа'
)]
class CleanupTemporaryUploadsCommand extends Command
{
    public function __construct(
        private readonly UploadRepository $uploadRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
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
            $io->success('Нет временных файлов для очистки');
            return Command::SUCCESS;
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

        $io->success("Очищено {$count} временных файлов");
        
        return Command::SUCCESS;
    }
}
