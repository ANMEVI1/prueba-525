<?php

use App\Services\BackupScheduleManager;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tarea Programada de Continuidad Operativa TI
| IESP Cap. FAP José Abelardo Quiñones
|--------------------------------------------------------------------------
| Ejecuta la extracción de bases de datos, compresión y cifrado AES-256
| durante la madrugada (por defecto 02:00 AM) de manera 100% automatizada.
*/
$scheduleEvent = Schedule::command('backup:auto-run')
    ->name('respaldo-automatico-institucional')
    ->withoutOverlapping()
    ->runInBackground();

try {
    $config = app(BackupScheduleManager::class)->getConfig();
    $frequency = $config['frequency'] ?? 'daily';
    $time = $config['time'] ?? '02:00';

    match ($frequency) {
        'every_minute' => $scheduleEvent->everyMinute(),
        'every_15_mins' => $scheduleEvent->everyFifteenMinutes(),
        'every_30_mins' => $scheduleEvent->everyThirtyMinutes(),
        'hourly' => $scheduleEvent->hourly(),
        'twice_daily' => $scheduleEvent->twiceDaily(2, 14),
        default => $scheduleEvent->dailyAt($time),
    };

    $scheduleEvent->when(fn () => (bool) ($config['enabled'] ?? true));
} catch (Throwable) {
    $scheduleEvent->dailyAt('02:00');
}
