<?php
// src/Document/Entity/Document.php

namespace App\Document\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Document\Repository\DocumentRepository;
use App\User\Entity\User;
use App\User\Entity\Company;
use App\Application\Entity\Application;
use App\Chat\Entity\Message;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\Table(name: '`documents`')]
class Document {

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['app:read', 'user:read', 'chat:read', 'doc:read'])]
    private int $id;

    // --- СВЯЗИ ---

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'uploader_user_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['doc:read'])]
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

    // --- ВЕРСИОННОСТЬ (Новое) ---

    /**
     * Ссылка на предыдущую версию документа (если это замена).
     */
    #[ORM\OneToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'parent_document_id', referencedColumnName: 'id', nullable: true)]
    #[Groups(['doc:read'])]
    private ?Document $parentDocument = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['doc:read'])]
    private bool $isArchived = false;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['doc:read'])]
    private ?string $versionComment = null; // Причина замены

    // --- ОСНОВНЫЕ ДАННЫЕ ---

    #[ORM\Column(type: 'string', length: 100)]
    #[Groups(['app:read', 'doc:read', 'chat:read'])]
    private string $docType; // e.g. 'ustav', 'passport', 'balance_f1'

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['app:read', 'doc:read', 'chat:read'])]
    private string $status = 'pending'; // enum: pending, approved, rejected

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['app:read', 'doc:read'])]
    private ?string $rejectionReason = null; // Если статус rejected

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['app:read', 'doc:read', 'chat:read'])]
    private string $fileName; // Оригинальное имя (scan.pdf)

    #[ORM\Column(type: 'text')]
    private string $filePath; // Путь в хранилище (не показываем в API напрямую, отдаем через контроллер скачивания)

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['doc:read'])]
    private ?int $fileSize = 0; // Размер в байтах

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['doc:read'])]
    private ?string $mimeType = null; // application/pdf, image/jpeg

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['app:read', 'doc:read', 'chat:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct() {
        $this->createdAt = new \DateTimeImmutable();
    }

    // --- ЛОГИКА ---

    /**
     * Проверяет, привязан ли документ хотя бы к одной сущности.
     */
    public function isLinked(): bool
    {
        return $this->company !== null
            || $this->application !== null
            || $this->message !== null;
    }

    // --- ГЕТТЕРЫ И СЕТТЕРЫ (Генерируются через IDE) ---

    public function getId(): int { return $this->id; }

    public function getUploaderUser(): User { return $this->uploader_user; }
    public function setUploaderUser(User $uploader_user): void { $this->uploader_user = $uploader_user; }

    public function getCompany(): ?Company { return $this->company; }
    public function setCompany(?Company $company): void { $this->company = $company; }

    public function getApplication(): ?Application { return $this->application; }
    public function setApplication(?Application $application): void { $this->application = $application; }

    public function getMessage(): ?Message { return $this->message; }
    public function setMessage(?Message $message): void { $this->message = $message; }

    public function getDocType(): string { return $this->docType; }
    public function setDocType(string $docType): void { $this->docType = $docType; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }

    public function getFileName(): string { return $this->fileName; }
    public function setFileName(string $fileName): void { $this->fileName = $fileName; }

    public function getFilePath(): string { return $this->filePath; }
    public function setFilePath(string $filePath): void { $this->filePath = $filePath; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    // Новые геттеры/сеттеры
    public function getParentDocument(): ?Document { return $this->parentDocument; }
    public function setParentDocument(?Document $parentDocument): void { $this->parentDocument = $parentDocument; }

    public function isArchived(): bool { return $this->isArchived; }
    public function setArchived(bool $isArchived): void { $this->isArchived = $isArchived; }

    public function getVersionComment(): ?string { return $this->versionComment; }
    public function setVersionComment(?string $versionComment): void { $this->versionComment = $versionComment; }

    public function getRejectionReason(): ?string { return $this->rejectionReason; }
    public function setRejectionReason(?string $rejectionReason): void { $this->rejectionReason = $rejectionReason; }

    public function getFileSize(): ?int { return $this->fileSize; }
    public function setFileSize(?int $fileSize): void { $this->fileSize = $fileSize; }

    public function getMimeType(): ?string { return $this->mimeType; }
    public function setMimeType(?string $mimeType): void { $this->mimeType = $mimeType; }
}
