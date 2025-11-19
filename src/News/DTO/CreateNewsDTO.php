<?php
// src/News/DTO/CreateNewsDTO.php
namespace App\News\DTO;
use Symfony\Component\Validator\Constraints as Assert;

class CreateNewsDTO {
    #[Assert\NotBlank]
    public string $title;

    #[Assert\NotBlank]
    public string $content;

    public bool $is_published = true;
}
