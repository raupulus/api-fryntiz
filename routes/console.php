<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Publicar contenidos programados cada hora
Schedule::command('content:publish')->hourly();

// Generar sitemap diariamente
Schedule::command('sitemap:generate')->daily();

// Obtener datos AEMET cada 6 horas
Schedule::command('aemet:adverse-events')->everySixHours();
Schedule::command('aemet:contamination')->everySixHours();
Schedule::command('aemet:predictions')->everySixHours();

// Mantenimiento de keycounter semanal
Schedule::command('keycounter:maintenance')->weekly();
