<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programación de tareas automáticas para ArchiveyCloud
Schedule::command('notifications:process-automatic')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/cron.log'));

// Tarea diaria para limpiar notificaciones antiguas
Schedule::command('notifications:cleanup')
    ->daily()
    ->at('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/cron.log'));

// Tarea semanal para generar reportes automáticos
Schedule::command('reports:generate-weekly')
    ->weekly()
    ->sundays()
    ->at('06:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/cron.log'));

// Tarea diaria para enviar resúmenes por email
Schedule::command('notifications:send-daily-summary')
    ->daily()
    ->at('08:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/cron.log'));
