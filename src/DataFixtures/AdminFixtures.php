<?php
// src/DataFixtures/AdminFixtures.php

namespace App\DataFixtures;

use App\User\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminFixtures extends Fixture
{
    // 1. Мы "внедряем" хешировщик паролей через конструктор
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // 2. Ищем, вдруг админ уже есть
        $existingAdmin = $manager->getRepository(User::class)->findOneBy(['email' => 'admin@leader-group.ru']);

        // 3. Если нет -> создаем
        if (!$existingAdmin) {
            $admin = new User();
            $admin->setEmail('admin@leader-group.ru');
            $admin->setFio('Главный Администратор');
            $admin->setPhone('000000000');

            // 4. Устанавливаем роль и статус (Админу не нужна аккредитация)
            $admin->setRole('admin');
            $admin->setStatus('active');

            // 5. Хешируем пароль (вместо 'admin' впишите свой сложный пароль)
            $hashedPassword = $this->passwordHasher->hashPassword(
                $admin,
                'admin_test_password'
            );
            $admin->setPasswordHash($hashedPassword);

            $manager->persist($admin);
            $manager->flush();
        }
    }
}
