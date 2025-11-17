<?php
// src/Kernel.php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    // --- ДОБАВЬТЕ ЭТОТ КОД ---

    /**
     * Переопределяем, чтобы хранить кеш в /tmp/ (внутри контейнера),
     * а не в /var/ (чтобы избежать проблем с правами на Windows).
     */
    public function getCacheDir(): string
    {
        return '/tmp/cache/' . $this->getEnvironment();
    }

    /**
     * Переопределяем, чтобы хранить логи в /tmp/ (внутри контейнера).
     */
    public function getLogDir(): string
    {
        return '/tmp/log/';
    }

    // --- КОНЕЦ КОДА ---
}
