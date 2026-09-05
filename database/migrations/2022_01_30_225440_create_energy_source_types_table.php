<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de tipos de fuente de energía (D107, D80).
 *
 * Tabla y no enum: se filtra por ella desde la API y desde la web, y un enum
 * obliga a un despliegue para añadir un tipo nuevo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('energy_source_types', function (Blueprint $table) {
            $table->comment('Tipos de fuente de energía: solar, eólica, autoabastecido…');
            $table->id();
            $table->string('slug', 64)->unique()->comment('Identificador estable para la API.');
            $table->string('name', 128)->comment('Nombre para mostrar.');
            $table->string('description', 512)->nullable();
            $table->string('icon', 64)->nullable()->comment('Icono para el panel y la web.');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        DB::table('energy_source_types')->insert([
            ['slug' => 'solar', 'name' => 'Fotovoltaica', 'description' => 'Paneles solares.', 'icon' => 'sun', 'position' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'wind', 'name' => 'Eólica', 'description' => 'Aerogeneradores.', 'icon' => 'wind', 'position' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'standalone', 'name' => 'Autoabastecido', 'description' => 'Nodo IoT con placa de 100-500 mA y batería de 500-2000 mAh.', 'icon' => 'cpu-chip', 'position' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'battery', 'name' => 'Batería', 'description' => 'Se carga a mano; no genera.', 'icon' => 'battery-50', 'position' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'grid', 'name' => 'Red eléctrica', 'description' => 'Suministro de red.', 'icon' => 'bolt', 'position' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('energy_source_types');
    }
};
