<?php
class Message {
    private int $id;

    // К какому чату (заявке) относится
    // Many-to-One: В Заявке много сообщений
    private Application $application; // (ForeignKey -> applications.id)

    // Кто отправил
    // Many-to-One: У User'а много сообщений
    private User $sender_user;     // (ForeignKey -> users.id)

    // Текст
    private string $body;

    // Модерация
    private string $moderation_status; // (Enum: 'pending', 'approved', 'rejected')
    private bool $read_status = false;

    private \DateTime $created_at;
}
?>