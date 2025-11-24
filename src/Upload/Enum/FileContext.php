<?php
// src/Upload/Enum/FileContext.php

namespace App\Upload\Enum;

/**
 * Enum для определения контекста использования файла в системе.
 * Позволяет разделять файлы по назначению и применять различные правила валидации.
 */
enum FileContext: string
{
    case DOCUMENT = 'document';              // Документы компании (устав, ИНН и т.д.)
    case AVATAR = 'avatar';                  // Аватар пользователя
    case CONTRACT_ATTACHMENT = 'contract';   // Вложения к договорам
    case CHAT_ATTACHMENT = 'chat';           // Файлы из чата
    case MESSAGE_ATTACHMENT = 'message';     // Вложения к сообщениям
    
    /**
     * Получить человекочитаемое название контекста.
     */
    public function getLabel(): string
    {
        return match($this) {
            self::DOCUMENT => 'Документ компании',
            self::AVATAR => 'Аватар пользователя',
            self::CONTRACT_ATTACHMENT => 'Вложение к договору',
            self::CHAT_ATTACHMENT => 'Файл из чата',
            self::MESSAGE_ATTACHMENT => 'Вложение к сообщению',
        };
    }

    public function isPublic(): bool
    {
        return match($this) {
            self::AVATAR => true,
            // self::NEWS_IMAGE => true, // если есть
            default => false, // Все документы по умолчанию приватные
        };
    }
    
    /**
     * Получить максимальный размер файла для контекста (в байтах).
     */
    public function getMaxFileSize(): int
    {
        return match($this) {
            self::AVATAR => 5 * 1024 * 1024,              // 5 MB для аватаров
            self::DOCUMENT => 20 * 1024 * 1024,           // 20 MB для документов
            self::CONTRACT_ATTACHMENT => 50 * 1024 * 1024, // 50 MB для договоров
            self::CHAT_ATTACHMENT => 10 * 1024 * 1024,    // 10 MB для чата
            self::MESSAGE_ATTACHMENT => 10 * 1024 * 1024, // 10 MB для сообщений
        };
    }
    
    /**
     * Получить разрешённые MIME-типы для контекста.
     * 
     * @return string[]
     */
    public function getAllowedMimeTypes(): array
    {
        return match($this) {
            self::AVATAR => [
                'image/jpeg',
                'image/png',
                'image/webp',
            ],
            self::DOCUMENT => [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',       // .xlsx
            ],
            self::CONTRACT_ATTACHMENT, 
            self::CHAT_ATTACHMENT, 
            self::MESSAGE_ATTACHMENT => [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/webp',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain',
                'application/zip',
            ],
        };
    }
}
