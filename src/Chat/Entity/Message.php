<?php
// src/Chat/Entity/Message.php

namespace App\Chat\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Chat\Repository\MessageRepository;
use App\Chat\Enum\ModerationStatus;
use App\Application\Entity\Application;
use App\User\Entity\User;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: 'messages')]
#[ORM\Index(name: 'idx_msg_application', columns: ['application_id'])]
#[ORM\Index(name: 'idx_sender', columns: ['sender_user_id'])]
#[ORM\Index(name: 'idx_moderation_status', columns: ['moderation_status'])]
#[ORM\Index(name: 'idx_msg_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_msg_app_created', columns: ['application_id', 'created_at'])]
class Message {

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['chat:read'])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Application::class)]
    #[ORM\JoinColumn(name: 'application_id', referencedColumnName: 'id', nullable: false)]
    private Application $application;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'sender_user_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['chat:read'])]
    private User $sender_user;

    #[ORM\Column(type: 'text')]
    #[Groups(['chat:read'])]
    private string $body;

    #[ORM\Column(type: 'string', enumType: ModerationStatus::class)]
    #[Groups(['chat:read'])]
    private ModerationStatus $moderation_status = ModerationStatus::PENDING;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['chat:read'])]
    private bool $read_status = false;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['chat:read'])]
    private \DateTimeImmutable $created_at;

    public function __construct() {
        $this->created_at = new \DateTimeImmutable();
    }

    // --- ГЕТТЕРЫ И СЕТТЕРЫ (ОБЯЗАТЕЛЬНЫ ДЛЯ API) ---

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

    public function getSenderUser(): User
    {
        return $this->sender_user;
    }

    public function setSenderUser(User $sender_user): void
    {
        $this->sender_user = $sender_user;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function getModerationStatus(): ModerationStatus
    {
        return $this->moderation_status;
    }

    public function setModerationStatus(ModerationStatus $moderation_status): void
    {
        $this->moderation_status = $moderation_status;
    }

    public function isReadStatus(): bool
    {
        return $this->read_status;
    }

    public function setReadStatus(bool $read_status): void
    {
        $this->read_status = $read_status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): void
    {
        $this->created_at = $created_at;
    }
}
