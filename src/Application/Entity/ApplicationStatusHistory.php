<?php
// src/Application/Entity/ApplicationStatusHistory.php

namespace App\Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Application\Repository\ApplicationStatusHistoryRepository;
use App\User\Entity\User;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ApplicationStatusHistoryRepository::class)]
#[ORM\Table(name: 'application_status_history')]
#[ORM\Index(name: 'idx_history_application', columns: ['application_id'])]
#[ORM\Index(name: 'idx_changed_by', columns: ['changed_by_user_id'])]
#[ORM\Index(name: 'idx_history_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_app_created', columns: ['application_id', 'created_at'])]
class ApplicationStatusHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['app:read', 'history:read'])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Application::class)]
    #[ORM\JoinColumn(name: 'application_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Application $application;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'changed_by_user_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['history:read'])]
    private User $changedBy;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['history:read'])]
    private string $oldStatus;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['history:read'])]
    private string $newStatus;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['history:read'])]
    private ?string $comment = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['history:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters and Setters

    public function getId(): int
    {
        return $this->id;
    }

    public function getApplication(): Application
    {
        return $this->application;
    }

    public function setApplication(Application $application): void
    {
        $this->application = $application;
    }

    public function getChangedBy(): User
    {
        return $this->changedBy;
    }

    public function setChangedBy(User $changedBy): void
    {
        $this->changedBy = $changedBy;
    }

    public function getOldStatus(): string
    {
        return $this->oldStatus;
    }

    public function setOldStatus(string $oldStatus): void
    {
        $this->oldStatus = $oldStatus;
    }

    public function getNewStatus(): string
    {
        return $this->newStatus;
    }

    public function setNewStatus(string $newStatus): void
    {
        $this->newStatus = $newStatus;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
