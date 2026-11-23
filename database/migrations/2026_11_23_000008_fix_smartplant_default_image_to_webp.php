<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sustituye el valor por defecto legado `smartplant/default.jpg` (inexistente en disco)
     * por `smartplant/default.webp` y normaliza las filas existentes afectadas.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE smartplant_plants ALTER COLUMN image SET DEFAULT 'smartplant/default.webp'");
        }

        DB::table('smartplant_plants')
            ->whereNull('image')
            ->orWhereIn('image', ['', 'smartplant/default.jpg'])
            ->update(['image' => 'smartplant/default.webp']);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE smartplant_plants ALTER COLUMN image SET DEFAULT 'smartplant/default.jpg'");
        }
    }
};
