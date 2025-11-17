<?php
// src/User/Entity/User.php

namespace App\User\Entity;

// 1. ПОДКЛЮЧАЕМ АТРИБУТЫ DOCTRINE
use Doctrine\ORM\Mapping as ORM;
use App\User\Repository\UserRepository; // (Мы создадим этот класс позже)
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Bank\Entity\Bank;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface; // <-- ДОБАВИТЬ
use Symfony\Component\Security\Core\User\UserInterface; // <-- ДОБАВИТЬ

// 2. ГОВОРИМ DOCTRINE, ЧТО ЭТО СУЩНОСТЬ
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`users`')] // Кавычки нужны, т.к. "user" - ключевое слово в SQL
class User implements UserInterface, PasswordAuthenticatedUserInterface {

    // 3. ОПИСЫВАЕМ КАЖДОЕ ПОЛЕ

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['app:read'])]
    private int $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Groups(['app:read'])]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['app:read'])]
    private string $password_hash;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['app:read'])]
    private string $role; // (Enum: 'client', 'agent', 'partner', 'admin')

    #[ORM\Column(type: 'string', length: 30)]
    #[Groups(['app:read'])]
    private string $status; // (Enum: 'pending_accreditation', 'active', 'rejected')

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['app:read'])]
    private string $fio;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['app:read'])]
    private string $phone;

    // --- ОПИСЫВАЕМ СВЯЗИ ---

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'personal_manager_id', referencedColumnName: 'id', nullable: true)]
    #[Groups(['app:read'])]
    private ?User $personal_manager;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'referrer_agent_id', referencedColumnName: 'id', nullable: true)]
    #[Groups(['app:read'])]
    private ?User $referrer_agent;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Company::class, cascade: ['persist', 'remove'])]
    #[Groups(['app:read'])]
    private ?Company $company;

    // --- 2. ДОБАВИТЬ ЭТО ПОЛЕ ---
    /**
     * Прямая связь "Сотрудник (Партнер) -> Банк".
     * Nullable, так как у Клиентов и Агентов банка нет.
     */
    #[ORM\ManyToOne(targetEntity: Bank::class)]
    #[ORM\JoinColumn(name: 'bank_id', referencedColumnName: 'id', nullable: true)]
    #[Groups(['app:read'])]
    private ?Bank $bank;
    // --- КОНЕЦ ДОБАВЛЕНИЯ ---

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['app:read'])]
    private \DateTimeImmutable $created_at;

    public function __construct() {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getPasswordHash(): string
    {
        return $this->password_hash;
    }

    public function setPasswordHash(string $password_hash): void
    {
        $this->password_hash = $password_hash;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    public function getFio(): string
    {
        return $this->fio;
    }

    public function setFio(string $fio): void
    {
        $this->fio = $fio;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getPersonalManager(): ?User
    {
        return $this->personal_manager;
    }

    public function setPersonalManager(?User $personal_manager): void
    {
        $this->personal_manager = $personal_manager;
    }

    public function getReferrerAgent(): ?User
    {
        return $this->referrer_agent;
    }

    public function setReferrerAgent(?User $referrer_agent): void
    {
        $this->referrer_agent = $referrer_agent;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): void
    {
        $this->company = $company;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): void
    {
        $this->created_at = $created_at;
    }

    /**
     * Возвращает роли.
     * Мы форматируем нашу 'admin' в 'ROLE_ADMIN' для Symfony.
     */
    public function getRoles(): array
    {
        $roles = ['ROLE_' . strtoupper($this->role)];
        $roles[] = 'ROLE_USER'; // Гарантируем, что у всех есть эта роль

        return array_unique($roles);
    }

    /**
     * Возвращает хешированный пароль (то, что требует PasswordAuthenticatedUserInterface).
     */
    public function getPassword(): string
    {
        return $this->password_hash;
    }

    /**
     * Возвращает "логин" пользователя (то, что требует UserInterface).
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * Метод для очистки временных паролей (нам не нужен).
     */
    public function eraseCredentials(): void
    {
        // Мы не храним пароли в открытом виде,
        // поэтому здесь ничего делать не нужно.
    }

    // ... ЗДЕСЬ НУЖНО СГЕНЕРИРОВАТЬ ГЕТТЕРЫ И СЕТТЕРЫ ...
    // (Нажмите Alt+Insert в PhpStorm)
}
?>
