<?php
class Document {
    private int $id;

    // Кто загрузил
    private User $uploader_user;   // (ForeignKey -> users.id)

    // К чему привязан (одно из полей должно быть НЕ null)

    // 1. Документ компании (аккредитация)
    // Many-to-One: У Компании много документов
    private ?Company $company;       // (ForeignKey -> companies.id)

    // 2. Документ по заявке
    // Many-to-One: У Заявки много документов
    private ?Application $application; // (ForeignKey -> applications.id)

    // 3. Документ из чата
    // One-to-One: У Сообщения один документ (можно сделать Many-to-Many, но так проще)
    private ?Message $message;       // (ForeignKey -> messages.id)

    // Описание файла
    private string $doc_type;      // (e.g., 'ustav', 'balance_f1', 'chat_file')
    private string $file_name;     // (Original name)
    private string $file_path;     // (S3 path)

    private \DateTime $created_at;
}
?>