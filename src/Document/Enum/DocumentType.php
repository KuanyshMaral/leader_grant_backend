<?php
// src/Document/Enum/DocumentType.php

namespace App\Document\Enum;

enum DocumentType: string
{
    case CARD_COMPANY = 'card_company';
    case PASSPORT_DIRECTOR = 'passport_director';
    case OGRN = 'ogrn';
    case INN = 'inn';
    case DECISION_DIRECTOR = 'decision_director';
    case LEASE_CONTRACT = 'lease_contract';
    case USTAV = 'ustav';
    case BALANCE_F1 = 'balance_f1';
    case REPORT_F2 = 'report_f2';
    case TAX_DECLARATION = 'tax_declaration';
    
    // Legacy / Other types
    case PASSPORT = 'passport'; // Keep for backward compatibility
    case TAX_REPORT = 'tax_report'; // Keep for backward compatibility
    case BANK_STATEMENT = 'bank_statement';
    case CONTRACT = 'contract';
    case QUESTIONNAIRE = 'questionnaire';
    case OTHER = 'other';
    
    public function label(): string
    {
        return match($this) {
            self::CARD_COMPANY => 'Карточка компании с реквизитами',
            self::PASSPORT_DIRECTOR => 'Паспорт руководителя (все страницы)',
            self::OGRN => 'Свидетельство ОГРН',
            self::INN => 'Свидетельство ИНН',
            self::DECISION_DIRECTOR => 'Решение/протокол о назначении руководителя',
            self::LEASE_CONTRACT => 'Договор аренды / Свидетельство о собственности',
            self::USTAV => 'Устав',
            self::BALANCE_F1 => 'Баланс Ф1',
            self::REPORT_F2 => 'Отчет о фин. результатах (форма №2)',
            self::TAX_DECLARATION => 'Налоговая декларация',
            
            self::PASSPORT => 'Паспорт (старый)',
            self::TAX_REPORT => 'Налоговая отчетность (старый)',
            self::BANK_STATEMENT => 'Банковская выписка',
            self::CONTRACT => 'Договор',
            self::QUESTIONNAIRE => 'Анкета',
            self::OTHER => 'Другое',
        };
    }
    
    public function isRequired(): bool
    {
        return in_array($this, [self::USTAV, self::PASSPORT, self::BALANCE_F1]);
    }
}
