<?php
// src/User/Entity/Company.php

namespace App\User\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\User\Repository\CompanyRepository;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[ORM\Table(name: '`companies`')]
class Company {

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['app:read', 'user:read'])]
    private int $id;

    // Связь: Какому User принадлежит эта Company
    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'company')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    // --- ОСНОВНАЯ ИНФОРМАЦИЯ ---

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private string $name; // Краткое наименование

    #[ORM\Column(type: 'text')]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private string $full_name; // Полное наименование

    #[ORM\Column(type: 'string', length: 12, unique: true)]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private string $inn;

    #[ORM\Column(type: 'string', length: 9, nullable: true)]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private ?string $kpp; // КПП

    #[ORM\Column(type: 'string', length: 15)]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private string $ogrn;

    #[ORM\Column(type: 'text')]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private string $legal_address; // Юридический адрес

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private ?string $actual_address; // Фактический адрес

    // --- РЕГИСТРАЦИОННЫЕ ДАННЫЕ ---

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private ?\DateTimeImmutable $registration_date; // Дата гос. регистрации

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private ?string $okpo; // ОКПО

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private ?string $oktmo; // ОКТМО

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private ?string $okved; // Основной ОКВЭД

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2, nullable: true)]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private ?string $authorized_capital; // Уставной капитал

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2, nullable: true)]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private ?string $paid_capital; // Оплаченный капитал

    // --- БИЗНЕС ПОКАЗАТЕЛИ ---

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private string $tax_system; // Система налогообложения (ОСН, УСН...)

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private ?int $employee_count; // Количество сотрудников

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private ?int $contract_count; // Количество контрактов (по 44/223-ФЗ)

    // --- СЛОЖНЫЕ СПИСКИ (JSON) ---

    /**
     * Банковские реквизиты (массив объектов).
     * Структура: [{bik, bank_name, checking_account, corr_account}, ...]
     */
    #[ORM\Column(type: 'json', options: ['default' => '[]'])]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private array $requisites = [];

    /**
     * Руководство / ЕИО (массив).
     * Структура: [{position, fio, birth_date, passport: {...}, ...}]
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private ?array $management = []; // Вместо одиночного ceo_fio

    /**
     * Учредители (Физлица и Юрлица).
     * Структура: [{type: 'fiz/ur', name, share_percent, ...}]
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private ?array $founders = [];

    /**
     * Лицензии и СРО.
     * Структура: [{number, date_start, date_end, issuer}, ...]
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private ?array $licenses = [];

    /**
     * Контактные лица.
     * Структура: [{fio, position, email, phone}, ...]
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private ?array $contact_persons = [];

    /**
     * Реквизиты счетов ЭТП (Площадки).
     * Структура: [{platform_name, account_number, ...}, ...]
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private ?array $etp_accounts = [];

    // --- СЛУЖЕБНЫЕ ---

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['app:read', 'user:read'])]
    private \DateTimeImmutable $created_at;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private ?string $webSite = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private ?string $officePhone = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[Groups(['app:read', 'user:read', 'company:write'])]
    private ?string $vatRate = null; // "20%", "No"

    public function __construct() {
        $this->created_at = new \DateTimeImmutable();
    }

    // --- ГЕТТЕРЫ И СЕТТЕРЫ ---

    public function getId(): int { return $this->id; }

    public function getUser(): User { return $this->user; }
    public function setUser(User $user): void { $this->user = $user; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }

    public function getFullName(): string { return $this->full_name; }
    public function setFullName(string $full_name): void { $this->full_name = $full_name; }

    public function getInn(): string { return $this->inn; }
    public function setInn(string $inn): void { $this->inn = $inn; }

    public function getKpp(): ?string { return $this->kpp; }
    public function setKpp(?string $kpp): void { $this->kpp = $kpp; }

    public function getOgrn(): string { return $this->ogrn; }
    public function setOgrn(string $ogrn): void { $this->ogrn = $ogrn; }

    public function getLegalAddress(): string { return $this->legal_address; }
    public function setLegalAddress(string $legal_address): void { $this->legal_address = $legal_address; }

    public function getActualAddress(): ?string { return $this->actual_address; }
    public function setActualAddress(?string $actual_address): void { $this->actual_address = $actual_address; }

    public function getRegistrationDate(): ?\DateTimeImmutable { return $this->registration_date; }
    public function setRegistrationDate(?\DateTimeImmutable $registration_date): void { $this->registration_date = $registration_date; }

    public function getOkpo(): ?string { return $this->okpo; }
    public function setOkpo(?string $okpo): void { $this->okpo = $okpo; }

    public function getOktmo(): ?string { return $this->oktmo; }
    public function setOktmo(?string $oktmo): void { $this->oktmo = $oktmo; }

    public function getOkved(): ?string { return $this->okved; }
    public function setOkved(?string $okved): void { $this->okved = $okved; }

    public function getAuthorizedCapital(): ?string { return $this->authorized_capital; }
    public function setAuthorizedCapital(?string $authorized_capital): void { $this->authorized_capital = $authorized_capital; }

    public function getPaidCapital(): ?string { return $this->paid_capital; }
    public function setPaidCapital(?string $paid_capital): void { $this->paid_capital = $paid_capital; }

    public function getTaxSystem(): string { return $this->tax_system; }
    public function setTaxSystem(string $tax_system): void { $this->tax_system = $tax_system; }

    public function getEmployeeCount(): ?int { return $this->employee_count; }
    public function setEmployeeCount(?int $employee_count): void { $this->employee_count = $employee_count; }

    public function getContractCount(): ?int { return $this->contract_count; }
    public function setContractCount(?int $contract_count): void { $this->contract_count = $contract_count; }

    // JSON Getters/Setters
    public function getRequisites(): array { return $this->requisites; }
    public function setRequisites(array $requisites): void { $this->requisites = $requisites; }

    public function getManagement(): ?array { return $this->management; }
    public function setManagement(?array $management): void { $this->management = $management; }

    public function getFounders(): ?array { return $this->founders; }
    public function setFounders(?array $founders): void { $this->founders = $founders; }

    public function getLicenses(): ?array { return $this->licenses; }
    public function setLicenses(?array $licenses): void { $this->licenses = $licenses; }

    public function getContactPersons(): ?array { return $this->contact_persons; }
    public function setContactPersons(?array $contact_persons): void { $this->contact_persons = $contact_persons; }

    public function getEtpAccounts(): ?array { return $this->etp_accounts; }
    public function setEtpAccounts(?array $etp_accounts): void { $this->etp_accounts = $etp_accounts; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->created_at; }

    public function getWebSite(): ?string { return $this->webSite; }
    public function setWebSite(?string $webSite): void { $this->webSite = $webSite; }

    public function getOfficePhone(): ?string { return $this->officePhone; }
    public function setOfficePhone(?string $officePhone): void { $this->officePhone = $officePhone; }

    public function getVatRate(): ?string { return $this->vatRate; }
    public function setVatRate(?string $vatRate): void { $this->vatRate = $vatRate; }
}
