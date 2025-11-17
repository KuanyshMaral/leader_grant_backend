<?php
// src/Application/Entity/Application.php

namespace App\Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Application\Repository\ApplicationRepository; // (Мы создадим этот класс позже)
use App\User\Entity\User;
use App\Bank\Entity\Bank;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ApplicationRepository::class)]
#[ORM\HasLifecycleCallbacks] // Нужно для автоматического updated_at
class Application {

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['app:read'])] // <-- ДОБАВИТЬ
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'client_user_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['app:read'])] // <-- ДОБАВИТЬ (Мы сериализуем User)
    private User $client_user;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'agent_user_id', referencedColumnName: 'id', nullable: true)]
    #[Groups(['app:read'])] // <-- ДОБАВИТЬ (Мы сериализуем Агента)
    private ?User $agent_user;

    #[ORM\ManyToOne(targetEntity: Bank::class)]
    #[ORM\JoinColumn(name: 'bank_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['app:read'])] // <-- ДОБАВИТЬ (Мы сериализуем Банк)
    private Bank $bank;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['app:read'])] // <-- ДОБАВИТЬ
    private string $product_type;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['app:read'])] // <-- ДОБАВИТЬ
    private string $status;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    #[Groups(['app:read'])] // <-- ДОБАВИТЬ
    private float $amount;

    #[ORM\Column(type: 'integer')]
    #[Groups(['app:read'])] // <-- ДОБАВИТЬ
    private int $term_days;

    #[ORM\Column(type: 'json')]
    #[Groups(['app:read'])] // <-- ДОБАВИТЬ
    private array $product_data;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['app:read'])] // <-- ДОБАВИТЬ
    private ?array $offer_data;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['app:read'])] // <-- ДОБАВИТЬ
    private \DateTimeImmutable $created_at;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['app:read'])] // <-- ДОБАВИТЬ
    private \DateTime $updated_at;

    public function __construct() {
        $this->created_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void {
        $this->updated_at = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getClientUser(): User
    {
        return $this->client_user;
    }

    public function setClientUser(User $client_user): void
    {
        $this->client_user = $client_user;
    }

    public function getAgentUser(): ?User
    {
        return $this->agent_user;
    }

    public function setAgentUser(?User $agent_user): void
    {
        $this->agent_user = $agent_user;
    }

    public function getBank(): Bank
    {
        return $this->bank;
    }

    public function setBank(Bank $bank): void
    {
        $this->bank = $bank;
    }

    public function getProductType(): string
    {
        return $this->product_type;
    }

    public function setProductType(string $product_type): void
    {
        $this->product_type = $product_type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function getTermDays(): int
    {
        return $this->term_days;
    }

    public function setTermDays(int $term_days): void
    {
        $this->term_days = $term_days;
    }

    public function getProductData(): array
    {
        return $this->product_data;
    }

    public function setProductData(array $product_data): void
    {
        $this->product_data = $product_data;
    }

    public function getOfferData(): ?array
    {
        return $this->offer_data;
    }

    public function setOfferData(?array $offer_data): void
    {
        $this->offer_data = $offer_data;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): void
    {
        $this->created_at = $created_at;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTime $updated_at): void
    {
        $this->updated_at = $updated_at;
    }

    // ... ЗДЕСЬ НУЖНО СГЕНЕРИРОВАТЬ ГЕТТЕРЫ И СЕТТЕРЫ ...
}
?>
