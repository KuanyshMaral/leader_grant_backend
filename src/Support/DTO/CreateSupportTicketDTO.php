<?php
// src/Support/DTO/CreateSupportTicketDTO.php

namespace App\Support\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateSupportTicketDTO
{
    #[Assert\NotBlank(message: 'Subject is required')]
    #[Assert\Length(max: 255, maxMessage: 'Subject cannot be longer than 255 characters')]
    public string $subject;

    #[Assert\NotBlank(message: 'Message is required')]
    #[Assert\Length(max: 5000, maxMessage: 'Message cannot be longer than 5000 characters')]
    public string $message;

    public ?int $attachment_id = null;
}
