<?php
// src/User/Entity/ClientAgentLink.php

namespace App\User\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ClientAgentLink {

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    // Связь: Агент (User)
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'agent_user_id', referencedColumnName: 'id', nullable: false)]
    private User $agent_user;

    // Связь: Клиент (User)
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'client_user_id', referencedColumnName: 'id', nullable: false)]
    private User $client_user;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getAgentUser(): User
    {
        return $this->agent_user;
    }

    public function setAgentUser(User $agent_user): void
    {
        $this->agent_user = $agent_user;
    }

    public function getClientUser(): User
    {
        return $this->client_user;
    }

    public function setClientUser(User $client_user): void
    {
        $this->client_user = $client_user;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    // ... ЗДЕСЬ НУЖНО СГЕНЕРИРОВАТЬ ГЕТТЕРЫ И СЕТТЕРЫ ...
}
?>
