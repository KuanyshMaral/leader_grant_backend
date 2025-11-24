<?php
// src/Upload/Entity/UploadedFile.php

namespace App\Upload\Entity;

use App\Upload\Enum\FileContext;
use App\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use App\Upload\Repository\UploadRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Универсальная сущность для всех загруженных файлов в системе.
 * Абстрагирует физическое хранение файлов от бизнес-логики.
 */
#[ORM\Entity(repositoryClass: UploadRepository::class)]
#[ORM\Table(name: '`uploaded_files`')]
class UploadedFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['file:read', 'doc:read'])]
    private int $id;

    /**
     * Пользователь, загрузивший файл.
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'uploaded_by_user_id', referencedColumnName: 'id', nullable: false)]
    private User $uploadedBy;

    /**
     * Контекст использования файла (документ, аватар, договор и т.д.).
     */
    #[ORM\Column(type: 'string', length: 50, enumType: FileContext::class)]
    #[Groups(['file:read'])]
    private FileContext $context;

    /**
     * ID связанной сущности (опционально).
     * Например, для контекста 'document' это будет ID документа.
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $contextId = null;

    /**
     * Оригинальное имя файла (как загрузил пользователь).
     */
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['file:read', 'doc:read'])]
    private string $originalFileName;

    /**
     * Имя файла в хранилище (UUID + расширение).
     */
    #[ORM\Column(type: 'string', length: 255)]
    private string $storedFileName;

    /**
     * Путь в хранилище (без имени файла).
     * Например: 'uploads/2024/11/documents'
     */
    #[ORM\Column(type: 'string', length: 500)]
    private string $storagePath;

    /**
     * Размер файла в байтах.
     */
    #[ORM\Column(type: 'integer')]
    #[Groups(['file:read', 'doc:read'])]
    private int $fileSize;

    /**
     * MIME-тип файла.
     */
    #[ORM\Column(type: 'string', length: 100)]
    #[Groups(['file:read', 'doc:read'])]
    private string $mimeType;

    /**
     * Дата и время загрузки.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['file:read'])]
    private \DateTimeImmutable $uploadedAt;

    /**
     * Опциональное описание файла.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * Флаг удаления (мягкое удаление).
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isDeleted = false;

    /**
     * Подтвержден ли файл (используется в системе).
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isConfirmed = false;

    /**
     * Дата подтверждения использования файла.
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

    /**
     * Временный файл (ожидает подтверждения).
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isTemporary = true;

    public function __construct()
    {
        $this->uploadedAt = new \DateTimeImmutable();
    }

    // --- Getters и Setters ---

    public function getId(): int
    {
        return $this->id;
    }

    public function getUploadedBy(): User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(User $uploadedBy): self
    {
        $this->uploadedBy = $uploadedBy;
        return $this;
    }

    public function getContext(): FileContext
    {
        return $this->context;
    }

    public function setContext(FileContext $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function getContextId(): ?string
    {
        return $this->contextId;
    }

    public function setContextId(?string $contextId): self
    {
        $this->contextId = $contextId;
        return $this;
    }

    public function getOriginalFileName(): string
    {
        return $this->originalFileName;
    }

    public function setOriginalFileName(string $originalFileName): self
    {
        $this->originalFileName = $originalFileName;
        return $this;
    }

    public function getStoredFileName(): string
    {
        return $this->storedFileName;
    }

    public function setStoredFileName(string $storedFileName): self
    {
        $this->storedFileName = $storedFileName;
        return $this;
    }

    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    public function setStoragePath(string $storagePath): self
    {
        $this->storagePath = $storagePath;
        return $this;
    }

    /**
     * Получить полный путь к файлу в хранилище.
     */
    public function getFullPath(): string
    {
        return $this->storagePath . '/' . $this->storedFileName;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): self
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getUploadedAt(): \DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setDeleted(bool $isDeleted): self
    {
        $this->isDeleted = $isDeleted;
        return $this;
    }

    public function isConfirmed(): bool
    {
        return $this->isConfirmed;
    }

    public function setConfirmed(bool $confirmed): self
    {
        $this->isConfirmed = $confirmed;
        if ($confirmed) {
            $this->confirmedAt = new \DateTimeImmutable();
            $this->isTemporary = false;
        }
        return $this;
    }

    public function getConfirmedAt(): ?\DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function isTemporary(): bool
    {
        return $this->isTemporary;
    }

    /**
     * Alias для совместимости.
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    #[Groups(['file:read', 'doc:read'])]
    #[SerializedName('path')]
    public function getPublicUrl(): ?string
    {
        if (!$this->context->isPublic()) {
            return null;
        }
        // Собираем полный путь для веба
        return '/uploads/' . $this->storagePath . '/' . $this->storedFileName;
    }
}
