<?php
// src/Shared/Trait/SoftDeletable.php

namespace App\Shared\Trait;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait для реализации Soft Delete.
 * Вместо физического удаления записи помечаются как удаленные.
 */
trait SoftDeletable
{
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;
    
    /**
     * Помечает сущность как удаленную.
     */
    public function delete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
    }
    
    /**
     * Восстанавливает удаленную сущность.
     */
    public function restore(): void
    {
        $this->deletedAt = null;
    }
    
    /**
     * Проверяет, удалена ли сущность.
     */
    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }
    
    /**
     * Возвращает дату удаления.
     */
    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }
}
