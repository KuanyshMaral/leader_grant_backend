<?php
// src/Agent/Entity/AgentClientInteraction.php

namespace App\Agent\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Agent\Repository\AgentClientInteractionRepository;
use App\User\Entity\User;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AgentClientInteractionRepository::class)]
#[ORM\Table(name: 'agent_client_interactions')]
#[ORM\Index(name: 'idx_agent', columns: ['agent_user_id'])]
#[ORM\Index(name: 'idx_client', columns: ['client_user_id'])]
#[ORM\Index(name: 'idx_interaction_date', columns: ['interaction_date'])]
#[ORM\Index(name: 'idx_agent_client', columns: ['agent_user_id', 'client_user_id'])]
class AgentClientInteraction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['interaction:read'])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'agent_user_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['interaction:read'])]
    private User $agent;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'client_user_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['interaction:read'])]
    private User $client;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['interaction:read'])]
    private string $type; // call, meeting, email, other

    #[ORM\Column(type: 'text')]
    #[Groups(['interaction:read'])]
    private string $notes;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['interaction:read'])]
    private \DateTimeImmutable $interactionDate;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['interaction:read'])]
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

    public function getClient(): User
    {
        return $this->client;
    }

    public function setClient(User $client): void
    {
        $this->client = $client;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): void
    {
        $this->notes = $notes;
    }

    public function getInteractionDate(): \DateTimeImmutable
    {
        return $this->interactionDate;
    }

    public function setInteractionDate(\DateTimeImmutable $interactionDate): void
    {
        $this->interactionDate = $interactionDate;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
