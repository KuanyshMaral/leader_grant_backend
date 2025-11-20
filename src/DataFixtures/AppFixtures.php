<?php

namespace App\DataFixtures;

use App\Application\Entity\Application;
use App\Bank\Entity\Bank;
use App\Chat\Entity\Message;
use App\User\Entity\Company;
use App\User\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // 1. БАНКИ
        $sber = new Bank();
        $sber->setName('Сбербанк');
        $sber->setConditions(['speed' => 'Высокая', 'tariffs' => ['bank_guarantee' => ['rate' => '2.5%']]]);
        $manager->persist($sber);

        $vtb = new Bank();
        $vtb->setName('ВТБ');
        $vtb->setConditions(['speed' => 'Средняя', 'tariffs' => ['bank_guarantee' => ['rate' => '2.8%']]]);
        $manager->persist($vtb);

        // 2. ПОЛЬЗОВАТЕЛИ

        // Админ
        $admin = new User();
        $admin->setEmail('admin@leader.ru');
        $admin->setFio('Администратор Системы');
        $admin->setRole('admin');
        $admin->setStatus('active');
        $admin->setPhone('+70000000000');
        $admin->setPasswordHash($this->hasher->hashPassword($admin, 'admin'));
        $manager->persist($admin);

        // Партнер (Сотрудник Сбера)
        $partner = new User();
        $partner->setEmail('sber@partner.ru');
        $partner->setFio('Менеджер Сбербанка');
        $partner->setRole('partner');
        $partner->setStatus('active');
        $partner->setPhone('+79001112233');
        $partner->setBank($sber); // Привязываем к банку
        $partner->setPasswordHash($this->hasher->hashPassword($partner, 'password'));
        $manager->persist($partner);

        // Агент
        $agent = new User();
        $agent->setEmail('agent@leader.ru');
        $agent->setFio('Агент Петров');
        $agent->setRole('agent');
        $agent->setStatus('active');
        $agent->setPhone('+79005556677');
        $agent->setPasswordHash($this->hasher->hashPassword($agent, 'password'));
        $manager->persist($agent);

        // Клиент
        $client = new User();
        $client->setEmail('client@company.ru');
        $client->setFio('Иванов Иван (Директор)');
        $client->setRole('client');
        $client->setStatus('active');
        $client->setPhone('+79009998877');
        $client->setPasswordHash($this->hasher->hashPassword($client, 'password'));
        $manager->persist($client);

        // 3. КОМПАНИЯ КЛИЕНТА
        $company = new Company();
        $company->setUser($client);
        $company->setInn('7705923378');
        $company->setName('ООО "ГАРАНТ ГРУПП"');
        $company->setFullName('ОБЩЕСТВО С ОГРАНИЧЕННОЙ ОТВЕТСТВЕННОСТЬЮ "ГАРАНТ ГРУПП"');
        $company->setLegalAddress('г. Москва, ул. Ленина 1');
        $company->setOgrn('1122334455');
        $company->setTaxSystem('OSN');
        $company->setCeoFio('Иванов Иван Иванович');
        $manager->persist($company);

        // 4. ЗАЯВКА (Демонстрационная)
        $app = new Application();
        $app->setClientUser($client);
        $app->setBank($sber);
        $app->setProductType('bank_guarantee');
        $app->setAmount(5000000);
        $app->setTermDays(365);
        $app->setStatus('bank_review'); // Уже на проверке у банка
        $app->setProductData(['procurement_number' => '0123456789', 'law' => '44-ФЗ']);
        $manager->persist($app);

        // 5. ЧАТ
        $msg1 = new Message();
        $msg1->setApplication($app);
        $msg1->setSenderUser($client);
        $msg1->setBody('Добрый день! Загрузил документы.');
        $msg1->setModerationStatus('approved');
        $manager->persist($msg1);

        $msg2 = new Message();
        $msg2->setApplication($app);
        $msg2->setSenderUser($partner); // Отвечает банк
        $msg2->setBody('Добрый день. Принято в работу.');
        $msg2->setModerationStatus('approved');
        $manager->persist($msg2);

        $manager->flush();
    }
}
