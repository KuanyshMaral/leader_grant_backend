<?php
// src/Document/Entity/Document.php

namespace App\Document\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Document\Repository\DocumentRepository;
use App\Document\Enum\DocumentStatus;
use App\Document\Enum\DocumentType;
use App\User\Entity\User;
use App\User\Entity\Company;
use App\Application\Entity\Application;
use App\Chat\Entity\Message;
use App\Upload\Entity\UploadedFile;
use App\Shared\Trait\SoftDeletable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\Table(name: '`documents`')]
#[ORM\Index(name: 'idx_uploader', columns: ['uploader_user_id'])]
#[ORM\Index(name: 'idx_company', columns: ['company_id'])]
#[ORM\Index(name: 'idx_doc_application', columns: ['application_id'])]
#[ORM\Index(name: 'idx_message', columns: ['message_id'])]
#[ORM\Index(name: 'idx_doc_status', columns: ['status'])]
#[ORM\Index(name: 'idx_doc_type', columns: ['doc_type'])]
#[ORM\Index(name: 'idx_uploaded_file', columns: ['uploaded_file_id'])]
class Document {
    use SoftDeletable; // ДОБАВЛЕНО: Мягкое удаление

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

    #[ORM\Column(type: 'string', enumType: DocumentType::class, length: 100)]
    #[Groups(['app:read', 'doc:read', 'chat:read'])]
    #[SerializedName('doc_type')]
    private DocumentType $docType;

    #[ORM\Column(type: 'string', enumType: DocumentStatus::class)]
    #[Groups(['app:read', 'doc:read', 'chat:read'])]
    private DocumentStatus $status = DocumentStatus::PENDING;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['app:read', 'doc:read'])]
    private ?string $rejectionReason = null; // Если статус rejected

    /**
     * Ссылка на загруженный файл.
     * НОВАЯ АРХИТЕКТУРА: файл хранится отдельно в модуле Upload.
     */
    #[ORM\OneToOne(targetEntity: UploadedFile::class)]
    #[ORM\JoinColumn(name: 'uploaded_file_id', referencedColumnName: 'id', nullable: true)]
    #[Groups(['doc:read'])]
    private ?UploadedFile $file = null;

    // DEPRECATED: Старые поля для обратной совместимости (будут удалены после миграции)
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $fileName_deprecated = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $filePath_deprecated = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $fileSize_deprecated = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $mimeType_deprecated = null;

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

    public function getDocType(): DocumentType { return $this->docType; }
    public function setDocType(DocumentType $docType): void { $this->docType = $docType; }

    public function getStatus(): DocumentStatus { return $this->status; }
    public function setStatus(DocumentStatus $status): void { $this->status = $status; }

    // --- NEW: File management ---
    
    public function getFile(): ?UploadedFile { return $this->file; }
    public function setFile(?UploadedFile $file): void { $this->file = $file; }

    // --- BACKWARD COMPATIBILITY: Поддержка старых методов через новую архитектуру ---
    
    /**
     * @deprecated Используйте $document->getFile()->getOriginalFileName()
     */
    public function getFileName(): string 
    { 
        // Если есть новый файл, берём имя оттуда
        if ($this->file) {
            return $this->file->getOriginalFileName();
        }
        // Иначе возвращаем старое поле
        return $this->fileName_deprecated ?? '';
    }
    
    /**
     * @deprecated Больше не используется, файл управляется через Upload модуль
     */
    public function setFileName(string $fileName): void 
    { 
        $this->fileName_deprecated = $fileName; 
    }

    /**
     * @deprecated Используйте $document->getFile()->getFullPath()
     */
    public function getFilePath(): string 
    { 
        if ($this->file) {
            return $this->file->getFullPath();
        }
        return $this->filePath_deprecated ?? '';
    }
    
    /**
     * @deprecated Больше не используется
     */
    public function setFilePath(string $filePath): void 
    { 
        $this->filePath_deprecated = $filePath; 
    }

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

    /**
     * @deprecated Используйте $document->getFile()->getFileSize()
     */
    public function getFileSize(): ?int 
    { 
        if ($this->file) {
            return $this->file->getFileSize();
        }
        return $this->fileSize_deprecated;
    }
    
    /**
     * @deprecated Больше не используется
     */
    public function setFileSize(?int $fileSize): void 
    { 
        $this->fileSize_deprecated = $fileSize; 
    }

    /**
     * @deprecated Используйте $document->getFile()->getMimeType()
     */
    public function getMimeType(): ?string 
    { 
        if ($this->file) {
            return $this->file->getMimeType();
        }
        return $this->mimeType_deprecated;
    }
    
    /**
     * @deprecated Больше не используется
     */
    public function setMimeType(?string $mimeType): void 
    { 
        $this->mimeType_deprecated = $mimeType; 
    }

    /**
     * Get public URL path to the document file
     */
    public function getPublicPath(): string
    {
        if ($this->file) {
            return $this->file->getPublicPath();
        }
        // Fallback to deprecated path
        return $this->filePath_deprecated ?? '';
    }
}
