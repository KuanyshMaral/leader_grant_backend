<?php
// src/User/Entity/User.php

namespace App\User\Entity;

// 1. ПОДКЛЮЧАЕМ АТРИБУТЫ DOCTRINE
use Doctrine\ORM\Mapping as ORM;
use App\User\Repository\UserRepository;
use App\User\Enum\UserRole;
use App\User\Enum\UserStatus;
use App\Bank\Entity\Bank;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`users`')]
#[ORM\Index(name: 'idx_email', columns: ['email'])]
#[ORM\Index(name: 'idx_role', columns: ['role'])]
#[ORM\Index(name: 'idx_user_status', columns: ['status'])]
#[ORM\Index(name: 'idx_user_accreditation', columns: ['accreditation_status'])]
#[ORM\Index(name: 'idx_referrer_agent', columns: ['referrer_agent_id'])]
#[ORM\Index(name: 'idx_personal_manager', columns: ['personal_manager_id'])]
#[ORM\Index(name: 'idx_user_bank', columns: ['bank_id'])]
#[ORM\Index(name: 'idx_role_status', columns: ['role', 'status'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface {

    // 3. ОПИСЫВАЕМ КАЖДОЕ ПОЛЕ

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['app:read', 'chat:read'])]
    private int $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Groups(['app:read'])]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $password_hash;

    #[ORM\Column(type: 'string', enumType: UserRole::class)]
    #[Groups(['app:read', 'chat:read'])]
    private UserRole $role;

    #[ORM\Column(type: 'string', enumType: UserStatus::class)]
    #[Groups(['app:read'])]
    private UserStatus $status = UserStatus::PENDING_REVIEW;

    // Поля для аккредитации (модерации пользователей админом)
    #[ORM\Column(type: 'string', length: 50, options: ['default' => 'pending'])]
    #[Groups(['app:read'])]
    private string $accreditationStatus = 'pending'; //pending, approved, rejected

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $rejectionReason = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $accreditationDate = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['app:read', 'chat:read'])]
    private string $fio;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['app:read'])]
    private string $phone;

    // --- ОПИСЫВАЕМ СВЯЗИ ---

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'personal_manager_id', referencedColumnName: 'id', nullable: true)]
    #[Groups(['app:read'])]
    private ?User $personal_manager = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'referrer_agent_id', referencedColumnName: 'id', nullable: true)]
    #[Groups(['app:read'])]
    private ?User $referrer_agent = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Company::class, cascade: ['persist', 'remove'])]
    #[Groups(['app:read'])]
    private ?Company $company = null;

    // --- 2. ДОБАВИТЬ ЭТО ПОЛЕ ---
    /**
     * Прямая связь "Сотрудник (Партнер) -> Банк".
     * Nullable, так как у Клиентов и Агентов банка нет.
     */
    #[ORM\ManyToOne(targetEntity: Bank::class)]
    #[ORM\JoinColumn(name: 'bank_id', referencedColumnName: 'id', nullable: true)]
    #[Groups(['app:read'])]
    private ?Bank $bank = null;
    // --- КОНЕЦ ДОБАВЛЕНИЯ ---

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['app:read'])]
    private \DateTimeImmutable $created_at;

    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    #[Groups(['app:read', 'user:write'])]
    private array $preferences = [];

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[Groups(['app:read', 'user:read'])]
    private ?string $gender = null; // 'male', 'female'

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['app:read', 'user:read'])]
    private ?string $timezone = null; // e.g. 'Europe/Moscow'

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['app:read', 'user:read'])]
    #[SerializedName('avatar')]
    private ?string $avatarPath = null; // Ссылка на фото

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    #[Groups(['app:read', 'user:read'])]
    private ?string $commissionRate = null; // Процент комиссии для агентов (например, 2.50)

    public function __construct() {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): int { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }

    public function getPassword(): string { return $this->password_hash; }
    public function setPasswordHash(string $password_hash): void { $this->password_hash = $password_hash; }

    public function getRole(): UserRole { return $this->role; }
    public function setRole(UserRole $role): void { $this->role = $role; }

    public function getStatus(): UserStatus { return $this->status; }
    public function setStatus(UserStatus $status): void { $this->status = $status; }

    public function getFio(): string { return $this->fio; }
    public function setFio(string $fio): void { $this->fio = $fio; }

    public function getPhone(): string { return $this->phone; }
    public function setPhone(string $phone): void { $this->phone = $phone; }

    public function getCompany(): ?Company { return $this->company; }
    public function setCompany(?Company $company): void { $this->company = $company; }

    public function getReferrerAgent(): ?User { return $this->referrer_agent; }
    public function setReferrerAgent(?User $referrer_agent): void { $this->referrer_agent = $referrer_agent; }

    public function getBank(): ?Bank { return $this->bank; }
    public function setBank(?Bank $bank): void { $this->bank = $bank; }

    public function getPreferences(): array { return $this->preferences; }
    public function setPreferences(array $preferences): void { $this->preferences = $preferences; }

    public function getGender(): ?string { return $this->gender; }
    public function setGender(?string $gender): void { $this->gender = $gender; }

    public function getTimezone(): ?string { return $this->timezone; }
    public function setTimezone(?string $timezone): void { $this->timezone = $timezone; }

    public function getAvatarPath(): ?string { return $this->avatarPath; }
    public function setAvatarPath(?string $avatarPath): void { $this->avatarPath = $avatarPath; }

    // Alias для API совместимости
    public function getAvatar(): ?string { return $this->avatarPath; }
    public function setAvatar(?string $avatar): void { $this->avatarPath = $avatar; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->created_at; }

    // --- ИНТЕРФЕЙСЫ SECURITY ---

    public function getRoles(): array {
        $roles = ['ROLE_' . strtoupper($this->role->value)];
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }


    public function getUserIdentifier(): string {
        return $this->email;
    }

    // Accreditation methods
    public function getAccreditationStatus(): string { return $this->accreditationStatus; }
    public function setAccreditationStatus(string $accreditationStatus): void { $this->accreditationStatus = $accreditationStatus; }

    public function getRejectionReason(): ?string { return $this->rejectionReason; }
    public function setRejectionReason(?string $rejectionReason): void { $this->rejectionReason = $rejectionReason; }

    public function getAccreditationDate(): ?\DateTimeImmutable { return $this->accreditationDate; }
    public function setAccreditationDate(?\DateTimeImmutable $accreditationDate): void { $this->accreditationDate = $accreditationDate; }

    public function getCommissionRate(): ?string { return $this->commissionRate; }
    public function setCommissionRate(?string $commissionRate): void { $this->commissionRate = $commissionRate; }

    public function eraseCredentials(): void {}
}
?>
