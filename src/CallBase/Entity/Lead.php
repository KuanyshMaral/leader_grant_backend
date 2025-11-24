<?php

namespace App\CallBase\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\User\Entity\User;
use App\Application\Entity\Application;
use App\CallBase\Enum\LeadStatus;
use App\CallBase\Repository\LeadRepository;

#[ORM\Entity(repositoryClass: LeadRepository::class)]
#[ORM\Table(name: 'leads')]
#[ORM\Index(name: 'idx_leads_status', columns: ['status'])]
#[ORM\Index(name: 'idx_leads_assigned', columns: ['assigned_to_user_id', 'status'])]
#[ORM\Index(name: 'idx_leads_inn', columns: ['inn'])]
class Lead
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;
    
    #[ORM\Column(type: 'string', length: 255)]
    private string $companyName;
    
    #[ORM\Column(type: 'string', length: 12)]
    private string $inn;
    
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $contactPerson = null;
    
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $phone = null;
    
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $email = null;
    
    #[ORM\Column(type: 'string', enumType: LeadStatus::class)]
    private LeadStatus $status;
    
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;
    
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'assigned_to_user_id', nullable: true)]
    private ?User $assignedTo = null;
    
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $assignedAt = null;
    
    #[ORM\ManyToOne(targetEntity: Application::class)]
    #[ORM\JoinColumn(name: 'converted_to_application_id', nullable: true)]
    private ?Application $convertedToApplication = null;
    
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $convertedAt = null;
    
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'converted_to_client_id', nullable: true)]
    private ?User $convertedToClient = null;
    
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $source = null;
    
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $sourceReference = null;
    
    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;
    
    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;
    
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_user_id', nullable: true)]
    private ?User $createdBy = null;
    
    public function __construct()
    {
        $this->status = LeadStatus::NEW;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }
    
    // Getters and Setters
    
    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getCompanyName(): string
    {
        return $this->companyName;
    }
    
    public function setCompanyName(string $companyName): self
    {
        $this->companyName = $companyName;
        return $this;
    }
    
    public function getInn(): string
    {
        return $this->inn;
    }
    
    public function setInn(string $inn): self
    {
        $this->inn = $inn;
        return $this;
    }
    
    public function getContactPerson(): ?string
    {
        return $this->contactPerson;
    }
    
    public function setContactPerson(?string $contactPerson): self
    {
        $this->contactPerson = $contactPerson;
        return $this;
    }
    
    public function getPhone(): ?string
    {
        return $this->phone;
    }
    
    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }
    
    public function getEmail(): ?string
    {
        return $this->email;
    }
    
    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }
    
    public function getStatus(): LeadStatus
    {
        return $this->status;
    }
    
    public function setStatus(LeadStatus $status): self
    {
        $this->status = $status;
        return $this;
    }
    
    public function getComment(): ?string
    {
        return $this->comment;
    }
    
    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }
    
    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }
    
    public function setAssignedTo(?User $assignedTo): self
    {
        $this->assignedTo = $assignedTo;
        return $this;
    }
    
    public function getAssignedAt(): ?\DateTime
    {
        return $this->assignedAt;
    }
    
    public function setAssignedAt(?\DateTime $assignedAt): self
    {
        $this->assignedAt = $assignedAt;
        return $this;
    }
    
    public function getConvertedToApplication(): ?Application
    {
        return $this->convertedToApplication;
    }
    
    public function setConvertedToApplication(?Application $convertedToApplication): self
    {
        $this->convertedToApplication = $convertedToApplication;
        return $this;
    }
    
    public function getConvertedAt(): ?\DateTime
    {
        return $this->convertedAt;
    }
    
    public function setConvertedAt(?\DateTime $convertedAt): self
    {
        $this->convertedAt = $convertedAt;
        return $this;
    }
    
    public function getConvertedToClient(): ?User
    {
        return $this->convertedToClient;
    }
    
    public function setConvertedToClient(?User $convertedToClient): self
    {
        $this->convertedToClient = $convertedToClient;
        return $this;
    }
    
    public function getSource(): ?string
    {
        return $this->source;
    }
    
    public function setSource(?string $source): self
    {
        $this->source = $source;
        return $this;
    }
    
    public function getSourceReference(): ?string
    {
        return $this->sourceReference;
    }
    
    public function setSourceReference(?string $sourceReference): self
    {
        $this->sourceReference = $sourceReference;
        return $this;
    }
    
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
    
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }
    
    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
    
    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }
    
    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }
    
    public function isConverted(): bool
    {
        return $this->convertedToApplication !== null;
    }
}
