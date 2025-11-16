<?php
// src/Integration/DTO/CompanyInfoDTO.php

namespace App\Integration\DTO;

// DTO для *ответа* нашему фронтенду
class CompanyInfoDTO {
    public string $name;
    public string $full_name;
    public string $inn;
    public string $ogrn;
    public string $legal_address;
    public string $ceo_fio;
    public string $status; // (Действующая / Ликвидирована)

    // Статический метод-фабрика для "очистки" ответа от checko.ru
    public static function fromCheckoResponse(array $dirtyData): self {
        $dto = new self();
        $dto->name = $dirtyData['НАИМ_СОКР'] ?? '';
        $dto->full_name = $dirtyData['НАИМ_ПОЛН'] ?? '';
        $dto->inn = $dirtyData['ИНН'] ?? '';
        $dto->ogrn = $dirtyData['ОГРН'] ?? '';
        $dto->legal_address = $dirtyData['АДРЕС']['АДРЕС_ПОЛН'] ?? '';
        $dto->ceo_fio = $dirtyData['РУКОВОДИТЕЛЬ']['ФИО'] ?? '';
        $dto->status = $dirtyData['СТАТУС']['НАИМ'] ?? 'Неизвестен';

        return $dto;
    }
}