<?php
// src/News/Entity/News.php

namespace App\News\Entity;

use App\News\Repository\NewsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: NewsRepository::class)]
class News
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['news:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['news:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['news:read'])]
    private ?string $content = null;

    #[ORM\Column]
    #[Groups(['news:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['news:read'])]
    private ?bool $isPublished = true;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ... (Геттеры и Сеттеры сгенерируйте через Alt+Insert)
    public function getId(): ?int { return $this->id; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getContent(): ?string { return $this->content; }
    public function setContent(string $content): static { $this->content = $content; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function isPublished(): ?bool { return $this->isPublished; }
    public function setPublished(bool $isPublished): static { $this->isPublished = $isPublished; return $this; }
}
