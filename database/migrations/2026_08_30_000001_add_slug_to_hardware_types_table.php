<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * `hardware_types` sólo tenía `name`, y el nombre lleva acentos y espacios
 * («Estación Meteorológica»). La API filtra por tipo —`GET /hardware/devices?type=`—
 * y no se puede pedir a un cliente que mande eso en una query string.
 *
 * El filtro ya estaba escrito contra una columna `slug` que no existía: la
 * petición reventaba con «column "slug" does not exist». Aquí se crea.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hardware_types', function (Blueprint $table) {
            $table->string('slug', 80)
                ->nullable()
                ->after('name')
                ->comment('Identificador estable para la API, sin acentos ni espacios');
        });

        // Relleno de lo que ya hubiera, con el nombre como origen.
        foreach (DB::table('hardware_types')->select('id', 'name')->get() as $tipo) {
            DB::table('hardware_types')
                ->where('id', $tipo->id)
                ->update(['slug' => Str::slug((string) $tipo->name)]);
        }

        Schema::table('hardware_types', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('hardware_types', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
