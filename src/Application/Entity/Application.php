<?php
class Application {
    private int $id;

    // Чья заявка?
    // Many-to-One: У Клиента много заявок
    private User $client_user;     // (ForeignKey -> users.id)

    // Кто подал? (Может быть null, если Клиент сам)
    // Many-to-One: У Агента много заявок
    private ?User $agent_user;      // (ForeignKey -> users.id)

    // Куда подали?
    // Many-to-One: У Банка много заявок
    private Bank $bank;            // (ForeignKey -> banks.id)

    // Что подали?
    private string $product_type;  // (Enum: 'bank_guarantee', 'credit', ...)
    private string $status;        // (Enum: 'draft', 'submitted', 'offer_received', ...)
    private float $amount;        // Сумма
    private int $term_days;     // Срок в днях

    // Детали (все, что пришло из Калькулятора)
    private array $product_data;    // (jsonb: № закупки, ИНН заказчика и т.д.)

    // Оферта от банка (когда появится)
    private ?array $offer_data;     // (jsonb: Ставка, комиссия от банка)

    private \DateTime $created_at;
    private \DateTime $updated_at;
}
?>