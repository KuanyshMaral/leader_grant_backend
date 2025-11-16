<?php
class Bank {
    private int $id;
    private string $name;
    private ?string $logo_path;

    // Главное поле: правила Калькулятора
    // (Стоп-факторы, тарифы, лимиты, скорость)
    private array $conditions;      // (jsonb)
}
?>