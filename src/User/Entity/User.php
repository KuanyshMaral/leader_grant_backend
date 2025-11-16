<?php
class User {
    // Primary Key
    private int $id;

    // Данные для входа
    private string $email;         // (Unique)
    private string $password_hash; // Хеш пароля

    // Роль и Статус
    private string $role;          // (Enum: 'client', 'agent', 'partner', 'admin')
    private string $status;        // (Enum: 'pending_accreditation', 'active', 'rejected')

    // Личные данные
    private string $fio;
    private string $phone;

    // Связи (Relationships)

    // Менеджер (сотрудник Leader_Group)
    // Many-to-One: Много пользователей у одного менеджера
    private ?User $personal_manager; // (ForeignKey -> users.id)

    // Агент, который привлек
    // Many-to-One: Много пользователей у одного рефера
    private ?User $referrer_agent;   // (ForeignKey -> users.id)

    // Компания пользователя (если он Клиент или Агент)
    // One-to-One: У одного User одна Company
    private ?Company $company;       // (Обратная связь с Company)

    private \DateTime $created_at;
}
?>