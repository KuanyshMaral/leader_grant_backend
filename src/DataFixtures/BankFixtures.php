<?php
// src/DataFixtures/BankFixtures.php

namespace App\DataFixtures;

use App\Bank\Entity\Bank;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class BankFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $bank1 = new Bank();
        $bank1->setName('Тестовый Банк 1 (Хороший)');
        $bank1->setConditions([
            'products' => ['bank_guarantee', 'credit'],
            'stop_factors' => ['inn_blacklist' => ['1234567890']],
            'laws' => ['44_fz' => true, '223_fz' => true],
            'limits' => [
                'bank_guarantee' => [
                    'min_amount' => 10000,
                    'max_amount' => 5000000,
                    'min_term_days' => 10,
                    'max_term_days' => 700,
                ],
            ],
            'tariffs' => [
                'bank_guarantee' => ['rate' => '2.5%']
            ],
            'speed' => 'Высокая'
        ]);
        $manager->persist($bank1);

        $bank2 = new Bank();
        $bank2->setName('Тестовый Банк 2 (Только 44-ФЗ)');
        $bank2->setConditions([
            'products' => ['bank_guarantee'],
            'stop_factors' => [],
            'laws' => ['44_fz' => true, '223_fz' => false], // Не работает с 223-ФЗ
            'limits' => [
                'bank_guarantee' => [
                    'min_amount' => 100000,
                    'max_amount' => 1000000,
                    'min_term_days' => 30,
                    'max_term_days' => 365,
                ],
            ],
            'tariffs' => [
                'bank_guarantee' => ['rate' => '3.1%']
            ],
            'speed' => 'Низкая'
        ]);
        $manager->persist($bank2);

        $manager->flush();
    }
}
