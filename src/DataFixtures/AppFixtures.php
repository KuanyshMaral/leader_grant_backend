<?php

namespace App\DataFixtures;

use App\Application\Entity\Application;
use App\Application\Entity\ApplicationStatusHistory;
use App\Bank\Entity\Bank;
use App\CallBase\Entity\Lead;
use App\Chat\Entity\Message;
use App\Document\Entity\Document;
use App\News\Entity\News;
use App\User\Entity\ClientAgentLink;
use App\User\Entity\Company;
use App\User\Entity\User;
use App\User\Enum\UserRole;
use App\User\Enum\UserStatus;
use App\Application\Enum\ApplicationStatus;
use App\Application\Enum\ProductType;
use App\CallBase\Enum\LeadStatus;
use App\Chat\Enum\ModerationStatus;
use App\Document\Enum\DocumentStatus;
use App\Document\Enum\DocumentType;
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
        // ========================================
        // 1. БАНКИ
        // ========================================
        $sber = new Bank();
        $sber->setName('Сбербанк');
        $sber->setConditions([
            'speed' => 'Высокая',
            'tariffs' => [
                'bank_guarantee' => ['rate' => '2.5%', 'min_amount' => 100000],
                'credit' => ['rate' => '12%']
            ]
        ]);
        $manager->persist($sber);

        $vtb = new Bank();
        $vtb->setName('ВТБ');
        $vtb->setConditions([
            'speed' => 'Средняя',
            'tariffs' => [
                'bank_guarantee' => ['rate' => '2.8%', 'min_amount' => 500000],
                'credit' => ['rate' => '13.5%']
            ]
        ]);
        $manager->persist($vtb);

        $alfa = new Bank();
        $alfa->setName('Альфа-Банк');
        $alfa->setConditions([
            'speed' => 'Высокая',
            'tariffs' => [
                'bank_guarantee' => ['rate' => '2.2%', 'min_amount' => 300000],
                'factoring' => ['rate' => '15%']
            ]
        ]);
        $manager->persist($alfa);

        $akbars = new Bank();
        $akbars->setName('АК Барс');
        $akbars->setConditions([
            'speed' => 'Средняя',
            'tariffs' => [
                'bank_guarantee' => ['rate' => '3.0%', 'min_amount' => 200000]
            ]
        ]);
        $manager->persist($akbars);

        // ========================================
        // 2. ПОЛЬЗОВАТЕЛИ
        // ========================================

        // АДМИН
        $admin = new User();
        $admin->setEmail('admin@leader.ru');
        $admin->setFio('Администратор Системы');
        $admin->setRole(UserRole::ADMIN);
        $admin->setStatus(UserStatus::ACTIVE);
        $admin->setPhone('+70000000000');
        $admin->setPasswordHash($this->hasher->hashPassword($admin, 'admin'));
        $manager->persist($admin);

        // ПАРТНЕРЫ (Сотрудники банков)
        $partnerSber = new User();
        $partnerSber->setEmail('sber@partner.ru');
        $partnerSber->setFio('Менеджер Сбербанка');
        $partnerSber->setRole(UserRole::PARTNER);
        $partnerSber->setStatus(UserStatus::ACTIVE);
        $partnerSber->setPhone('+79001112233');
        $partnerSber->setBank($sber);
        $partnerSber->setPasswordHash($this->hasher->hashPassword($partnerSber, 'password'));
        $manager->persist($partnerSber);

        $partnerVtb = new User();
        $partnerVtb->setEmail('vtb@partner.ru');
        $partnerVtb->setFio('Кредитный менеджер ВТБ');
        $partnerVtb->setRole(UserRole::PARTNER);
        $partnerVtb->setStatus(UserStatus::ACTIVE);
        $partnerVtb->setPhone('+79002223344');
        $partnerVtb->setBank($vtb);
        $partnerVtb->setPasswordHash($this->hasher->hashPassword($partnerVtb, 'password'));
        $manager->persist($partnerVtb);

        // АГЕНТЫ
        $agent1 = new User();
        $agent1->setEmail('agent@leader.ru');
        $agent1->setFio('Агент Петров Иван');
        $agent1->setRole(UserRole::AGENT);
        $agent1->setStatus(UserStatus::ACTIVE);
        $agent1->setPhone('+79005556677');
        $agent1->setPasswordHash($this->hasher->hashPassword($agent1, 'password'));
        $manager->persist($agent1);

        $agent2 = new User();
        $agent2->setEmail('agent2@leader.ru');
        $agent2->setFio('Агентесса Сидорова Мария');
        $agent2->setRole(UserRole::AGENT);
        $agent2->setStatus(UserStatus::ACTIVE);
        $agent2->setPhone('+79006667788');
        $agent2->setPasswordHash($this->hasher->hashPassword($agent2, 'password'));
        $manager->persist($agent2);

        // КЛИЕНТЫ
        $client1 = new User();
        $client1->setEmail('client@company.ru');
        $client1->setFio('Иванов Иван (Директор)');
        $client1->setRole(UserRole::CLIENT);
        $client1->setStatus(UserStatus::ACTIVE);
        $client1->setPhone('+79009998877');
        $client1->setPasswordHash($this->hasher->hashPassword($client1, 'password'));
        $manager->persist($client1);

        $client2 = new User();
        $client2->setEmail('client2@stroigroup.ru');
        $client2->setFio('Смирнов Петр (Ген.дир)');
        $client2->setRole(UserRole::CLIENT);
        $client2->setStatus(UserStatus::ACTIVE);
        $client2->setPhone('+79007776655');
        $client2->setPasswordHash($this->hasher->hashPassword($client2, 'password'));
        $manager->persist($client2);

        $client3 = new User();
        $client3->setEmail('client3@techprom.ru');
        $client3->setFio('Кузнецова Анна');
        $client3->setRole(UserRole::CLIENT);
        $client3->setStatus(UserStatus::PENDING_REVIEW); // Новый клиент без аккредитации
        $client3->setPhone('+79008887766');
        $client3->setPasswordHash($this->hasher->hashPassword($client3, 'password'));
        $manager->persist($client3);

        $manager->flush(); // Flush users first

        // ========================================
        // 3. КОМПАНИИ КЛИЕНТОВ
        // ========================================
        $company1 = new Company();
        $company1->setUser($client1);
        $company1->setInn('7705923378');
        $company1->setName('ООО "ГАРАНТ ГРУПП"');
        $company1->setFullName('ОБЩЕСТВО С ОГРАНИЧЕННОЙ ОТВЕТСТВЕННОСТЬЮ "ГАРАНТ ГРУПП"');
        $company1->setLegalAddress('г. Москва, ул. Ленина 1');
        $company1->setOgrn('1122334455667');
        $company1->setKpp('770501001');
        $company1->setTaxSystem('OSN');
        $company1->setCeoFio('Иванов Иван Иванович');
        $company1->setRegistrationDate(new \DateTimeImmutable('2015-03-15'));
        $company1->setAuthorizedCapital(500000);
        $company1->setEmployeeCount(25);
        $company1->setRequisites([
            ['name' => 'Расчетный счет', 'value' => '40702810100000012345'],
            ['name' => 'Банк', 'value' => 'ПАО Сбербанк'],
            ['name' => 'БИК', 'value' => '044525225'],
            ['name' => 'К/С', 'value' => '30101810400000000225']
        ]);
        $manager->persist($company1);

        $company2 = new Company();
        $company2->setUser($client2);
        $company2->setInn('5029177289');
        $company2->setName('АО "СТРОЙ-ВЕСТ"');
        $company2->setFullName('АКЦИОНЕРНОЕ ОБЩЕСТВО "СТРОЙ-ВЕСТ"');
        $company2->setLegalAddress('г. Санкт-Петербург, Невский пр. 100');
        $company2->setOgrn('1025000123456');
        $company2->setKpp('502901001');
        $company2->setTaxSystem('USN');
        $company2->setCeoFio('Смирнов Петр Алексеевич');
        $company2->setRegistrationDate(new \DateTimeImmutable('2010-06-20'));
        $company2->setAuthorizedCapital(1000000);
        $company2->setEmployeeCount(50);
        $manager->persist($company2);

        $company3 = new Company();
        $company3->setUser($client3);
        $company3->setInn('7727474581');
        $company3->setName('ООО "ТЕХНОПРОМ"');
        $company3->setFullName('ОБЩЕСТВО С ОГРАНИЧЕННОЙ ОТВЕТСТВЕННОСТЬЮ "ТЕХНОПРОМ"');
        $company3->setLegalAddress('г. Казань, ул. Баумана 50');
        $company3->setOgrn('1161690123456');
        $company3->setKpp('772701001');
        $company3->setTaxSystem('OSN');
        $company3->setCeoFio('Кузнецова Анна Сергеевна');
        $company3->setRegistrationDate(new \DateTimeImmutable('2018-09-10'));
        $company3->setAuthorizedCapital(300000);
        $company3->setEmployeeCount(15);
        $manager->persist($company3);

        $manager->flush();

        // ========================================
        // 4. СВЯЗИ АГЕНТ-КЛИЕНТ
        // ========================================
        $link1 = new ClientAgentLink();
        $link1->setAgentUser($agent1);
        $link1->setClientUser($client1);
        $link1->setStatus('linked');
        $manager->persist($link1);

        $link2 = new ClientAgentLink();
        $link2->setAgentUser($agent1);
        $link2->setClientUser($client2);
        $link2->setStatus('linked');
        $manager->persist($link2);

        $link3 = new ClientAgentLink();
        $link3->setAgentUser($agent2);
        $link3->setClientUser($client3);
        $link3->setStatus('linked');
        $manager->persist($link3);

        $manager->flush();

        // ========================================
        // 5. ЗАЯВКИ
        // ========================================

        // Заявка 1: Bank Guarantee - на проверке у банка
        $app1 = new Application();
        $app1->setClientUser($client1);
        $app1->setAgentUser($agent1);
        $app1->setBank($sber);
        $app1->setProductType(ProductType::BANK_GUARANTEE);
        $app1->setAmount(5000000);
        $app1->setTermDays(365);
        $app1->setStatus(ApplicationStatus::UNDER_REVIEW);
        $app1->setProductData([
            'procurement_number' => '0373100002421000123',
            'law' => '44-ФЗ',
            'customer' => 'ФГУП ПОЧТА РОССИИ',
            'guarantee_type' => 'Обеспечение исполнения контракта'
        ]);
        $manager->persist($app1);

        // Заявка 2: Credit - оферта получена
        $app2 = new Application();
        $app2->setClientUser($client2);
        $app2->setAgentUser($agent1);
        $app2->setBank($vtb);
        $app2->setProductType(ProductType::CREDIT);
        $app2->setAmount(10000000);
        $app2->setTermDays(730);
        $app2->setStatus(ApplicationStatus::OFFER_RECEIVED);
        $app2->setTariffRate(13.5);
        $app2->setCommissionAmount('150000');
        $app2->setProductData([
            'purpose' => 'Пополнение оборотных средств',
            'collateral' => 'Недвижимость'
        ]);
        $app2->setOfferData([
            'interest_rate' => '13.5%',
            'monthly_payment' => '450000',
            'documents_required' => ['Устав', 'Баланс за последний год', 'Выписка ЕГРЮЛ']
        ]);
        $manager->persist($app2);

        // Заявка 3: Bank Guarantee - черновик
        $app3 = new Application();
        $app3->setClientUser($client1);
        $app3->setAgentUser($agent1);
        $app3->setBank($alfa);
        $app3->setProductType(ProductType::BANK_GUARANTEE);
        $app3->setAmount(2500000);
        $app3->setTermDays(180);
        $app3->setStatus(ApplicationStatus::DRAFT);
        $app3->setProductData([
            'procurement_number' => '0173200001520000456',
            'law' => '223-ФЗ',
            'customer' => 'ГУП ВОДОКАНАЛ',
            'guarantee_type' => 'Обеспечение заявки'
        ]);
        $manager->persist($app3);

        // Заявка 4: Factoring - отклонена
        $app4 = new Application();
        $app4->setClientUser($client2);
        $app4->setAgentUser($agent1);
        $app4->setBank($alfa);
        $app4->setProductType(ProductType::FACTORING);
        $app4->setAmount(3000000);
        $app4->setTermDays(90);
        $app4->setStatus(ApplicationStatus::REJECTED);
        $app4->setProductData([
            'rejection_reason' => 'Недостаточная кредитная история контрагентов',
            'counterparties' => ['ООО Компания А', 'АО Компания Б']
        ]);
        $manager->persist($app4);

        // Заявка 5: RKO - завершена
        $app5 = new Application();
        $app5->setClientUser($client3);
        $app5->setAgentUser($agent2);
        $app5->setBank($sber);
        $app5->setProductType(ProductType::RKO);
        $app5->setAmount(0);
        $app5->setTermDays(0);
        $app5->setStatus(ApplicationStatus::COMPLETED);
        $app5->setProductData([
            'account_number' => '40702810100000067890',
            'tariff_plan' => 'Малый бизнес'
        ]);
        $manager->persist($app5);

        $manager->flush();

        // ========================================
        // 6. ИСТОРИЯ СТАТУСОВ
        // ========================================
        $history1 = new ApplicationStatusHistory();
        $history1->setApplication($app1);
        $history1->setChangedBy($admin);
        $history1->setOldStatus('draft');
        $history1->setNewStatus('bank_review');
        $history1->setComment('Заявка отправлена в банк');
        $manager->persist($history1);

        $history2 = new ApplicationStatusHistory();
        $history2->setApplication($app2);
        $history2->setChangedBy($partnerVtb);
        $history2->setOldStatus('bank_review');
        $history2->setNewStatus('offer_received');
        $history2->setComment('Оферта одобрена');
        $manager->persist($history2);

        $history3 = new ApplicationStatusHistory();
        $history3->setApplication($app4);
        $history3->setChangedBy($partnerVtb);
        $history3->setOldStatus('bank_review');
        $history3->setNewStatus('rejected');
        $history3->setComment('Недостаточная кредитная история');
        $manager->persist($history3);

        $manager->flush();

        // ========================================
        // 7. ЛИДЫ (CallBase)
        // ========================================
        $lead1 = new Lead();
        $lead1->setCompanyName('ООО "МЕГАСТРОЙ"');
        $lead1->setInn('4027132717');
        $lead1->setContactPerson('Дмитрий Иванович');
        $lead1->setPhone('+7 (916) 111-22-33');
        $lead1->setEmail('info@megastroi.ru');
        $lead1->setStatus(LeadStatus::NEW);
        $lead1->setAssignedTo($agent1);
        $lead1->setCreatedBy($admin);
        $manager->persist($lead1);

        $lead2 = new Lead();
        $lead2->setCompanyName('ИП Сидоров В.Г.');
        $lead2->setInn('7727474582');
        $lead2->setContactPerson('Валерий');
        $lead2->setPhone('+7 (903) 999-88-77');
        $lead2->setStatus(LeadStatus::PROCESS);
        $lead2->setComment('Попросил перезвонить во вторник');
        $lead2->setAssignedTo($agent1);
        $lead2->setCreatedBy($admin);
        $manager->persist($lead2);

        $lead3 = new Lead();
        $lead3->setCompanyName('ООО "ГЛОБАЛ ТРЕЙД"');
        $lead3->setInn('5904285581');
        $lead3->setContactPerson('Секретарь');
        $lead3->setPhone('+7 (800) 200-00-00');
        $lead3->setEmail('office@globaltrade.ru');
        $lead3->setStatus(LeadStatus::SUCCESS);
        $lead3->setComment('Согласились на расчет БГ!');
        $lead3->setConvertedToApplication($app1);
        $lead3->setConvertedToClient($client1);
        $lead3->setConvertedAt(new \DateTime('-5 days'));
        $lead3->setAssignedTo($agent1);
        $lead3->setCreatedBy($admin);
        $manager->persist($lead3);

        $lead4 = new Lead();
        $lead4->setCompanyName('АО "ТЕХНОПРОМ"');
        $lead4->setInn('5029177290');
        $lead4->setContactPerson('Ольга (Бухгалтер)');
        $lead4->setPhone('+7 (495) 123-45-67');
        $lead4->setStatus(LeadStatus::REJECTED);
        $lead4->setComment('Уже работают с другим банком');
        $lead4->setAssignedTo($agent2);
        $lead4->setCreatedBy($admin);
        $manager->persist($lead4);

        $manager->flush();

        // ========================================
        // 8. ЧАТ (Сообщения)
        // ========================================
        $msg1 = new Message();
        $msg1->setApplication($app1);
        $msg1->setSenderUser($client1);
        $msg1->setBody('Добрый день! Загрузил все необходимые документы по закупке.');
        $msg1->setModerationStatus(ModerationStatus::APPROVED);
        $manager->persist($msg1);

        $msg2 = new Message();
        $msg2->setApplication($app1);
        $msg2->setSenderUser($partnerSber);
        $msg2->setBody('Добрый день. Спасибо, документы приняты в работу. Срок рассмотрения 3 рабочих дня.');
        $msg2->setModerationStatus(ModerationStatus::APPROVED);
        $manager->persist($msg2);

        $msg3 = new Message();
        $msg3->setApplication($app1);
        $msg3->setSenderUser($client1);
        $msg3->setBody('Когда ожидать ответ?');
        $msg3->setModerationStatus(ModerationStatus::PENDING);
        $manager->persist($msg3);

        $msg4 = new Message();
        $msg4->setApplication($app2);
        $msg4->setSenderUser($partnerVtb);
        $msg4->setBody('Оферта одобрена! Условия кредитования направлены в личный кабинет.');
        $msg4->setModerationStatus(ModerationStatus::APPROVED);
        $manager->persist($msg4);

        $msg5 = new Message();
        $msg5->setApplication($app2);
        $msg5->setSenderUser($client2);
        $msg5->setBody('Отлично! Когда можно подъехать для подписания договора?');
        $msg5->setModerationStatus(ModerationStatus::APPROVED);
        $manager->persist($msg5);

        $manager->flush();

        // ========================================
        // 9. НОВОСТИ
        // ========================================
        $news1 = new News();
        $news1->setTitle('Новые тарифы Сбербанка на банковские гарантии');
        $news1->setContent('С 1 января 2025 года Сбербанк снижает ставки на банковские гарантии для малого и среднего бизнеса. Новая ставка составит от 2.2% годовых.');
        $news1->setPublished(true);
        $manager->persist($news1);

        $news2 = new News();
        $news2->setTitle('Изменения в законодательстве о закупках');
        $news2->setContent('Правительство РФ внесло изменения в 44-ФЗ, упрощающие процедуру получения банковских гарантий для участия в госзакупках.');
        $news2->setPublished(true);
        $manager->persist($news2);

        $news3 = new News();
        $news3->setTitle('ВТБ запустил программу льготного кредитования');
        $news3->setContent('Новая программа от ВТБ предлагает кредиты для малого бизнеса под 9.9% годовых сроком до 5 лет.');
        $news3->setPublished(true);
        $manager->persist($news3);

        $news4 = new News();
        $news4->setTitle('[ЧЕРНОВИК] Альфа-Банк расширяет линейку продуктов');
        $news4->setContent('Скоро будет объявлено о новых продуктах для корпоративных клиентов.');
        $news4->setPublished(false);
        $manager->persist($news4);

        $manager->flush();

        // ========================================
        // 10. ДОКУМЕНТЫ (Mock)
        // ========================================
        $doc1 = new Document();
        $doc1->setUploaderUser($client1);
        $doc1->setCompany($company1);
        $doc1->setDocType(DocumentType::BALANCE_F1);
        $doc1->setFileName('balance_2024.pdf');
        $doc1->setFilePath('/uploads/docs/balance_2024.pdf');
        $doc1->setFileSize(1024000);
        $doc1->setMimeType('application/pdf');
        $doc1->setStatus(DocumentStatus::APPROVED);
        $manager->persist($doc1);

        $doc2 = new Document();
        $doc2->setUploaderUser($client1);
        $doc2->setCompany($company1);
        $doc2->setDocType(DocumentType::USTAV);
        $doc2->setFileName('ustav.pdf');
        $doc2->setFilePath('/uploads/docs/ustav.pdf');
        $doc2->setFileSize(2048000);
        $doc2->setMimeType('application/pdf');
        $doc2->setStatus(DocumentStatus::APPROVED);
        $manager->persist($doc2);

        $doc3 = new Document();
        $doc3->setUploaderUser($client2);
        $doc3->setCompany($company2);
        $doc3->setDocType(DocumentType::OTHER);
        $doc3->setFileName('inn_svidetelstvo.pdf');
        $doc3->setFilePath('/uploads/docs/inn_svidetelstvo.pdf');
        $doc3->setFileSize(512000);
        $doc3->setMimeType('application/pdf');
        $doc3->setStatus(DocumentStatus::PENDING);
        $manager->persist($doc3);

        $manager->flush();

        echo "✅ Fixtures loaded successfully!\n";
        echo "Users created:\n";
        echo "  - Admin: admin@leader.ru / admin\n";
        echo "  - Partner (Sber): sber@partner.ru / password\n";
        echo "  - Partner (VTB): vtb@partner.ru / password\n";
        echo "  - Agent 1: agent@leader.ru / password\n";
        echo "  - Agent 2: agent2@leader.ru / password\n";
        echo "  - Client 1: client@company.ru / password\n";
        echo "  - Client 2: client2@stroigroup.ru / password\n";
        echo "  - Client 3: client3@techprom.ru / password\n";
        echo "\nData created:\n";
        echo "  - 4 Banks\n";
        echo "  - 8 Users (1 admin, 2 partners, 2 agents, 3 clients)\n";
        echo "  - 3 Companies\n";
        echo "  - 5 Applications (various statuses)\n";
        echo "  - 4 Leads (CallBase)\n";
        echo "  - 5 Chat Messages\n";
        echo "  - 4 News Articles\n";
        echo "  - 3 Documents\n";
    }
}
