<?php
// src/User/Enum/Gender.php

namespace App\User\Enum;

enum Gender: string
{
    case MALE = 'male';
    case FEMALE = 'female';
    case OTHER = 'other';
    case PREFER_NOT_TO_SAY = 'prefer_not_to_say';
    
    public function label(): string
    {
        return match($this) {
            self::MALE => 'Мужской',
            self::FEMALE => 'Женский',
            self::OTHER => 'Другой',
            self::PREFER_NOT_TO_SAY => 'Предпочитаю не указывать',
        };
    }
}
