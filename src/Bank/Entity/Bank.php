<?php
// src/Bank/Entity/Bank.php

namespace App\Bank\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Bank\Repository\BankRepository;
use App\Bank\Enum\BankStatus;
use App\Bank\Enum\AccreditationStatus;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BankRepository::class)]
#[ORM\Table(name: 'banks')]
#[ORM\Index(name: 'idx_bank_status', columns: ['status'])]
#[ORM\Index(name: 'idx_bank_accreditation', columns: ['accreditation_status'])]
class Bank {

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    // 'app:read' - для Клиента (когда он видит заявку)
        // 'bank:admin:read' - для Админа (в админке банков)
    #[Groups(['app:read', 'bank:admin:read'])]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['app:read', 'bank:admin:read'])]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['app:read', 'bank:admin:read'])]
    private ?string $logo_path;

    #[ORM\Column(type: 'json')]
    // 'conditions' (тарифы) видит ТОЛЬКО Админ
    #[Groups(['bank:admin:read'])]
    private array $conditions;

    #[ORM\Column(type: 'string', enumType: BankStatus::class)]
    #[Groups(['bank:admin:read'])]
    private BankStatus $status = BankStatus::ACTIVE;

    #[ORM\Column(type: 'string', enumType: AccreditationStatus::class)]
    #[Groups(['bank:admin:read'])]
    private AccreditationStatus $accreditationStatus = AccreditationStatus::APPROVED;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['bank:admin:read'])]
    private ?\DateTimeImmutable $accreditationDate = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['bank:admin:read'])]
    private ?string $rejectionReason = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getLogoPath(): ?string
    {
        return $this->logo_path;
    }

    public function setLogoPath(?string $logo_path): void
    {
        $this->logo_path = $logo_path;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function setConditions(array $conditions): void
    {
        $this->conditions = $conditions;
    }

    public function getStatus(): BankStatus
    {
        return $this->status;
    }

    public function setStatus(BankStatus $status): void
    {
        $this->status = $status;
    }

    public function getAccreditationStatus(): AccreditationStatus
    {
        return $this->accreditationStatus;
    }

    public function setAccreditationStatus(AccreditationStatus $accreditationStatus): void
    {
        $this->accreditationStatus = $accreditationStatus;
    }

    public function getAccreditationDate(): ?\DateTimeImmutable
    {
        return $this->accreditationDate;
    }

    public function setAccreditationDate(?\DateTimeImmutable $accreditationDate): void
    {
        $this->accreditationDate = $accreditationDate;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): void
    {
        $this->rejectionReason = $rejectionReason;
    }
}
?>
