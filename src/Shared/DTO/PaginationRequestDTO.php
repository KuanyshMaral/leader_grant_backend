<?php
// src/Shared/DTO/PaginationRequestDTO.php

namespace App\Shared\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class PaginationRequestDTO {
    /**
     * @Assert\Type("numeric")
     * @Assert\Positive(message="Страница должна быть > 0")
     */
    public int $page;

    /**
     * @Assert\Type("numeric")
     * @Assert\Positive
     * @Assert\Range(min=5, max=100, notInRangeMessage="Лимит должен быть от 5 до 100")
     */
    public int $limit;

    // Конструктор с дефолтными значениями
    public function __construct(mixed $page = 1, mixed $limit = 20) {
        $this->page = (int)$page > 0 ? (int)$page : 1;
        $this->limit = (int)$limit > 0 ? (int)$limit : 20;
    }
}