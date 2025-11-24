<?php
// src/Scheduler/UploadCleanupSchedule.php

namespace App\Scheduler;

use App\Upload\Message\CleanupTemporaryUploadsMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('upload_cleanup')]
class UploadCleanupSchedule implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(
                // Запускать каждые 15 минут
                RecurringMessage::every('15 minutes', new CleanupTemporaryUploadsMessage())
            );
    }
}
