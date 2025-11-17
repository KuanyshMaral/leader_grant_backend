<?php
// src/Bank/Entity/Bank.php

namespace App\Bank\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Bank\Repository\BankRepository; // <-- 1. ДОБАВЛЕНО
use Symfony\Component\Serializer\Annotation\Groups; // <-- Убедитесь, что это тоже есть

#[ORM\Entity]
class Bank {

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    // 'app:read' - для Клиента (когда он видит заявку)
        // 'bank:admin:read' - для Админа (в админке банков)
    #[Groups(['app:read', 'bank:admin:read'])]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['app:read', 'bank:admin:read'])]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['app:read', 'bank:admin:read'])]
    private ?string $logo_path;

    #[ORM\Column(type: 'json')]
    // 'conditions' (тарифы) видит ТОЛЬКО Админ
    #[Groups(['bank:admin:read'])]
    private array $conditions;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getLogoPath(): ?string
    {
        return $this->logo_path;
    }

    public function setLogoPath(?string $logo_path): void
    {
        $this->logo_path = $logo_path;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function setConditions(array $conditions): void
    {
        $this->conditions = $conditions;
    }

    // ... ЗДЕСЬ НУЖНО СГЕНЕРИРОВАТЬ ГЕТТЕРЫ И СЕТТЕРЫ ...
}
?>
