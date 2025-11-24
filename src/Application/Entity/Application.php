<?php
// src/Application/Entity/Application.php

namespace App\Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Application\Repository\ApplicationRepository;
use App\Application\Enum\ApplicationStatus;
use App\Application\Enum\ProductType;
use App\User\Entity\User;
use App\Bank\Entity\Bank;
use App\Shared\Trait\SoftDeletable;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ApplicationRepository::class)]
#[ORM\Table(name: 'applications')]
#[ORM\Index(name: 'idx_client_user', columns: ['client_user_id'])]
#[ORM\Index(name: 'idx_agent_user', columns: ['agent_user_id'])]
#[ORM\Index(name: 'idx_application_bank', columns: ['bank_id'])]
#[ORM\Index(name: 'idx_app_status', columns: ['status'])]
#[ORM\Index(name: 'idx_product_type', columns: ['product_type'])]
#[ORM\Index(name: 'idx_app_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_updated_at', columns: ['updated_at'])]
#[ORM\Index(name: 'idx_status_product', columns: ['status', 'product_type'])]
#[ORM\HasLifecycleCallbacks]
class Application {
    use SoftDeletable; // ДОБАВЛЕНО: Мягкое удаление

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

    #[ORM\Column(type: 'string', enumType: ProductType::class)]
    #[Groups(['app:read'])]
    private ProductType $product_type;

    #[ORM\Column(type: 'string', enumType: ApplicationStatus::class)]
    #[Groups(['app:read'])]
    private ApplicationStatus $status = ApplicationStatus::DRAFT;

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

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2, nullable: true)]
    #[Groups(['app:read'])]
    private ?string $commissionAmount = null; // Сумма к оплате (комиссия банка)

    #[ORM\Column(type: 'float', nullable: true)]
    #[Groups(['app:read'])]
    private ?float $tariffRate = null; // Тариф в % (например, 2.5)

    public function __construct() {
        $this->created_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void {
        $this->updated_at = new \DateTime();
    }

    public function getCommissionAmount(): ?string { return $this->commissionAmount; }
    public function setCommissionAmount(?string $commissionAmount): void { $this->commissionAmount = $commissionAmount; }

    public function getTariffRate(): ?float { return $this->tariffRate; }
    public function setTariffRate(?float $tariffRate): void { $this->tariffRate = $tariffRate; }

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

    public function getProductType(): ProductType
    {
        return $this->product_type;
    }

    public function setProductType(ProductType $product_type): void
    {
        $this->product_type = $product_type;
    }

    public function getStatus(): ApplicationStatus
    {
        return $this->status;
    }

    public function setStatus(ApplicationStatus $status): void
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
