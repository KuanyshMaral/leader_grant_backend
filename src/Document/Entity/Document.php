<?php
// src/Document/Entity/Document.php

namespace App\Document\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Document\Repository\DocumentRepository; // (Мы создадим этот класс позже)
use App\User\Entity\User;
use App\User\Entity\Company;
use App\Application\Entity\Application;
use App\Chat\Entity\Message;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
class Document {

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'uploader_user_id', referencedColumnName: 'id', nullable: false)]
    private User $uploader_user;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id', nullable: true)]
    private ?Company $company;

    #[ORM\ManyToOne(targetEntity: Application::class)]
    #[ORM\JoinColumn(name: 'application_id', referencedColumnName: 'id', nullable: true)]
    private ?Application $application;

    #[ORM\OneToOne(targetEntity: Message::class)]
    #[ORM\JoinColumn(name: 'message_id', referencedColumnName: 'id', nullable: true)]
    private ?Message $message;

    #[ORM\Column(type: 'string', length: 100)]
    private string $doc_type;

    #[ORM\Column(type: 'string', length: 255)]
    private string $file_name;

    #[ORM\Column(type: 'text')]
    private string $file_path;

    #[ORM\Column(type: 'datetime_immutable')]
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

    public function getUploaderUser(): User
    {
        return $this->uploader_user;
    }

    public function setUploaderUser(User $uploader_user): void
    {
        $this->uploader_user = $uploader_user;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): void
    {
        $this->company = $company;
    }

    public function getApplication(): ?Application
    {
        return $this->application;
    }

    public function setApplication(?Application $application): void
    {
        $this->application = $application;
    }

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function setMessage(?Message $message): void
    {
        $this->message = $message;
    }

    public function getDocType(): string
    {
        return $this->doc_type;
    }

    public function setDocType(string $doc_type): void
    {
        $this->doc_type = $doc_type;
    }

    public function getFileName(): string
    {
        return $this->file_name;
    }

    public function setFileName(string $file_name): void
    {
        $this->file_name = $file_name;
    }

    public function getFilePath(): string
    {
        return $this->file_path;
    }

    public function setFilePath(string $file_path): void
    {
        $this->file_path = $file_path;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): void
    {
        $this->created_at = $created_at;
    }

    public function isLinked(): bool
    {
        return $this->company !== null || $this->application !== null || $this->message !== null;
    }

    // ... ЗДЕСЬ НУЖНО СГЕНЕРИРОВАТЬ ГЕТТЕРЫ И СЕТТЕРЫ ...
}
?>
