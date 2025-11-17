<?php
// src/Chat/Entity/Message.php

namespace App\Chat\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Chat\Repository\MessageRepository; // (Мы создадим этот класс позже)
use App\Application\Entity\Application;
use App\User\Entity\User;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message {

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['chat:read'])] // <-- 2. ДОБАВИТЬ ГРУППУ
    private int $id;

    #[ORM\ManyToOne(targetEntity: Application::class)]
    #[ORM\JoinColumn(name: 'application_id', referencedColumnName: 'id', nullable: false)]
    // (Мы не хотим всю заявку в чате, только ID)
    private Application $application;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'sender_user_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['chat:read'])] // <-- 3. ДОБАВИТЬ (чтобы показать, кто отправил)
    private User $sender_user;

    #[ORM\Column(type: 'text')]
    #[Groups(['chat:read'])] // <-- 4. ДОБАВИТЬ
    private string $body;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['chat:read'])] // <-- 5. ДОБАВИТЬ (pending/approved)
    private string $moderation_status;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['chat:read'])] // <-- 6. ДОБАВИТЬ
    private bool $read_status = false;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['chat:read'])] // <-- 7. ДОБАВИТЬ (время отправки)
    private \DateTimeImmutable $created_at;

    public function __construct() {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getApplication(): Application
    {
        return $this->application;
    }

    public function setApplication(Application $application): void
    {
        $this->application = $application;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function getSenderUser(): User
    {
        return $this->sender_user;
    }

    public function setSenderUser(User $sender_user): void
    {
        $this->sender_user = $sender_user;
    }

    public function getModerationStatus(): string
    {
        return $this->moderation_status;
    }

    public function setModerationStatus(string $moderation_status): void
    {
        $this->moderation_status = $moderation_status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): void
    {
        $this->created_at = $created_at;
    }

    public function isReadStatus(): bool
    {
        return $this->read_status;
    }

    public function setReadStatus(bool $read_status): void
    {
        $this->read_status = $read_status;
    }

    // ... ЗДЕСЬ НУЖНО СГЕНЕРИРОВАТЬ ГЕТТЕРЫ И СЕТТЕРЫ ...
}
?>
