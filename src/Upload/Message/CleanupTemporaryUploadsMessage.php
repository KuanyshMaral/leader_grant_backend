<?php
// src/Upload/Message/CleanupTemporaryUploadsMessage.php

namespace App\Upload\Message;

/**
 * Сообщение для запуска очистки временных файлов.
 * Будет запускаться автоматически каждые 15 минут через Symfony Scheduler.
 */
class CleanupTemporaryUploadsMessage
{
    // Пустое сообщение - просто триггер для очистки
}
