<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Añade la columna `slug` a la tabla `hardware_devices` para permitir
 * rutas amigables sin exponer IDs numéricos en la web pública.
 *
 * Durante la migración, transforma automáticamente los nombres existentes
 * en slugs únicos garantizando que no queden valores nulos ni duplicados.
 */
return new class extends Migration
{
    private string $tableName = 'hardware_devices';

    public function up(): void
    {
        // 1. Añadir columna slug inicialmente nullable para permitir la población de datos
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->string('slug', 255)->nullable()->after('name')
                ->comment('Identificador amigable único para URLs públicas');
        });

        // 2. Poblar slugs únicos para todos los registros existentes
        $devices = DB::table($this->tableName)->orderBy('id')->get();
        $usedSlugs = [];

        foreach ($devices as $device) {
            $baseName = $device->name ?: "device-{$device->id}";
            $baseSlug = Str::slug($baseName);
            if ($baseSlug === '') {
                $baseSlug = "device-{$device->id}";
            }

            $slug = $baseSlug;
            $counter = 2;
            while (in_array($slug, $usedSlugs, true)) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }

            $usedSlugs[] = $slug;

            DB::table($this->tableName)
                ->where('id', $device->id)
                ->update(['slug' => $slug]);
        }

        // 3. Establecer slug como NOT NULL y crear índice UNIQUE
        DB::statement("ALTER TABLE {$this->tableName} ALTER COLUMN slug SET NOT NULL");

        Schema::table($this->tableName, function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
