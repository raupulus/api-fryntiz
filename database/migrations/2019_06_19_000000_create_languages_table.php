<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateLanguagesTable
 */
class CreateLanguagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->char('locale', 8)
                ->unique()
                ->comment('Código del país e idioma: es_ES');
            $table->char('iso_locale', 8)
                ->unique()
                ->comment('Código del país e idioma: es-ES');
            $table->char('iso2', 2)
                ->unique()
                ->comment('Código del país con longitud 2: es');
            $table->char('iso3', 3)
                ->unique()
                ->comment('Código del país con longitud 3: esp');
            $table->string('name', 255)->comment('Nombre del idioma en su propio idioma');
            $table->string('iso_language', 255)->comment('Nombre del idioma en inglés: spanish');
            $table->string('icon16', 511)
                ->nullable()
                ->comment('Icono a 16x16 píxeles');
            $table->string('icon32', 511)
                ->nullable()
                ->comment('Icono a 32x32 píxeles');
            $table->string('icon64', 511)
                ->nullable()
                ->comment('Icono a 64x64 píxeles');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('languages');
    }
}
