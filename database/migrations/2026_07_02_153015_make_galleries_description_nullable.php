<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La columna description de galleries era NOT NULL sin valor por defecto,
 * lo que impedía crear una galería sin escribir descripción. El módulo de
 * galerías se implementa ahora por primera vez (antes no tenía modelo ni
 * recurso Filament), así que se corrige aquí antes de su primer uso real.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE galleries ALTER COLUMN description DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE galleries SET description = '' WHERE description IS NULL");
        DB::statement('ALTER TABLE galleries ALTER COLUMN description SET NOT NULL');
    }
};
