<?php
// src/Agent/Entity/AgentCommission.php

namespace App\Agent\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Agent\Repository\AgentCommissionRepository;
use App\Agent\Enum\CommissionStatus;
use App\User\Entity\User;
use App\Application\Entity\Application;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AgentCommissionRepository::class)]
#[ORM\Table(name: 'agent_commissions')]
#[ORM\UniqueConstraint(name: 'unique_commission_per_application', columns: ['application_id'])]
#[ORM\Index(name: 'idx_agent_commission', columns: ['agent_user_id'])]
#[ORM\Index(name: 'idx_commission_status', columns: ['status'])]
#[ORM\Index(name: 'idx_commission_created_at', columns: ['created_at'])]
class AgentCommission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['commission:read'])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'agent_user_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['commission:read'])]
    private User $agent;

    #[ORM\ManyToOne(targetEntity: Application::class)]
    #[ORM\JoinColumn(name: 'application_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['commission:read'])]
    private Application $application;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    #[Groups(['commission:read'])]
    private string $commissionRate; // Процент (например, 2.50)

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    #[Groups(['commission:read'])]
    private string $commissionAmount; // Сумма комиссии

    #[ORM\Column(type: 'string', enumType: CommissionStatus::class)]
    #[Groups(['commission:read'])]
    private CommissionStatus $status = CommissionStatus::PENDING;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['commission:read'])]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['commission:read'])]
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

    public function getAgent(): User
    {
        return $this->agent;
    }

    public function setAgent(User $agent): void
    {
        $this->agent = $agent;
    }

    public function getApplication(): Application
    {
        return $this->application;
    }

    public function setApplication(Application $application): void
    {
        $this->application = $application;
    }

    public function getCommissionRate(): string
    {
        return $this->commissionRate;
    }

    public function setCommissionRate(string $commissionRate): void
    {
        $this->commissionRate = $commissionRate;
    }

    public function getCommissionAmount(): string
    {
        return $this->commissionAmount;
    }

    public function setCommissionAmount(string $commissionAmount): void
    {
        $this->commissionAmount = $commissionAmount;
    }

    public function getStatus(): CommissionStatus
    {
        return $this->status;
    }

    public function setStatus(CommissionStatus $status): void
    {
        $this->status = $status;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): void
    {
        $this->paidAt = $paidAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
