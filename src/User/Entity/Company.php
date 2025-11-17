<?php
// src/User/Entity/Company.php

namespace App\User\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\User\Repository\CompanyRepository; // (Мы создадим этот класс позже)

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
class Company {

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    // Связь: Какому User принадлежит эта Company
    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'company')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text')]
    private string $full_name;

    #[ORM\Column(type: 'string', length: 12, unique: true)]
    private string $inn;

    #[ORM\Column(type: 'string', length: 15)]
    private string $ogrn;

    #[ORM\Column(type: 'text')]
    private string $legal_address;

    #[ORM\Column(type: 'string', length: 255)]
    private string $ceo_fio;

    #[ORM\Column(type: 'string', length: 50)]
    private string $tax_system;

    #[ORM\Column(type: 'json')]
    private array $requisites;

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

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getFullName(): string
    {
        return $this->full_name;
    }

    public function setFullName(string $full_name): void
    {
        $this->full_name = $full_name;
    }

    public function getInn(): string
    {
        return $this->inn;
    }

    public function setInn(string $inn): void
    {
        $this->inn = $inn;
    }

    public function getOgrn(): string
    {
        return $this->ogrn;
    }

    public function setOgrn(string $ogrn): void
    {
        $this->ogrn = $ogrn;
    }

    public function getCeoFio(): string
    {
        return $this->ceo_fio;
    }

    public function setCeoFio(string $ceo_fio): void
    {
        $this->ceo_fio = $ceo_fio;
    }

    public function getLegalAddress(): string
    {
        return $this->legal_address;
    }

    public function setLegalAddress(string $legal_address): void
    {
        $this->legal_address = $legal_address;
    }

    public function getTaxSystem(): string
    {
        return $this->tax_system;
    }

    public function setTaxSystem(string $tax_system): void
    {
        $this->tax_system = $tax_system;
    }

    public function getRequisites(): array
    {
        return $this->requisites;
    }

    public function setRequisites(array $requisites): void
    {
        $this->requisites = $requisites;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): void
    {
        $this->created_at = $created_at;
    }

    // ... ЗДЕСЬ НУЖНО СГЕНЕРИРОВАТЬ ГЕТТЕРЫ И СЕТТЕРЫ ...
}?>
