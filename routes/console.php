<?php

use Illuminate\Support\Facades\Schedule;

// Rotina de manutenção do motor de reservas.
// Em produção: um cron chamando `php artisan schedule:run` a cada minuto.
// Localmente: `php artisan schedule:work`.
Schedule::command('reservations:housekeeping')->everyFiveMinutes()->withoutOverlapping();
