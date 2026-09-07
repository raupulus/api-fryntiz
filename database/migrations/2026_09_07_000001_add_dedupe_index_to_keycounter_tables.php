<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Índice de apoyo para `keycounter:remove_duplicate`.
 *
 * Sin índice sobre `created_at`, filtrar por ventana temporal reciente sigue
 * obligando a un seq scan de la tabla completa: la ganancia real de la
 * ventana solo aparece con este índice delante (medido en local: de ~300ms a
 * ~2ms sobre 650k filas, index-only scan). `created_at` va primero porque es
 * el predicado del `WHERE`; el resto de columnas son las que usa el
 * `PARTITION BY` para detectar duplicados, en el mismo orden que la consulta.
 *
 * CONCURRENTLY porque estas tablas reciben escritura constante de
 * dispositivos IoT en producción: construir el índice sin bloquear inserts.
 */
return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        DB::statement(
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS keycounter_keyboard_dedupe_index '
            .'ON keycounter_keyboard (created_at, hardware_device_id, start_at, end_at, pulsations, id)'
        );

        DB::statement(
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS keycounter_mouse_dedupe_index '
            .'ON keycounter_mouse (created_at, hardware_device_id, start_at, end_at, total_clicks, id)'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS keycounter_keyboard_dedupe_index');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS keycounter_mouse_dedupe_index');
    }
};
