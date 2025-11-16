<?php
class SendMessageDTO {
    /**
     * @Assert\NotBlank(message="ID заявки обязателен")
     */
    public int $application_id;

    /**
     * @Assert\NotBlank(message="Сообщение не может быть пустым")
     */
    public string $body;

    // ID файла, если есть вложение.
    // Валидация самого файла (загрузка) - это отдельный эндпоинт.
    public ?int $document_id;
}
?>