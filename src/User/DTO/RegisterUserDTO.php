<?php
// src/User/DTO/RegisterUserDTO.php

namespace App\User\DTO;
use Symfony\Component\Validator\Constraints as Assert;
class RegisterUserDTO {
    /**
     * @Assert\NotBlank(message="Email не может быть пустым")
     * @Assert\Email(message="Некорректный email")
     */
    public string $email;

    /**
     * @Assert\NotBlank(message="Пароль не может быть пустым")
     * @Assert\Length(min=8, minMessage="Пароль должен быть минимум 8 символов")
     */
    public string $password;

    /**
     * @Assert\NotBlank
     * @Assert\Choice(choices={"client", "agent"}, message="Неверная роль")
     */
    public string $role;

    /**
     * @Assert\NotBlank(message="ФИО не может быть пустым")
     */
    public string $fio;

    /**
     * @Assert\NotBlank(message="Телефон не может быть пустым")
     */
    public string $phone;

    // ID агента, если регистрация по реферальной ссылке
    public ?int $referrer_agent_id;
}
?>
