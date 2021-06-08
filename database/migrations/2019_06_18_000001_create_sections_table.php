<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateSectionsTable
 *
 * Tabla para distinguir en secciones cada tipo de contenido, por ejemplo el que
 * se crea para una web de informática, para el curriculum, para los vuelos,
 * para la estación meteorológica...
 *
 */
class CreateSectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');

            $table->string('title', 511)
                ->index()
                ->comment('Título de la sección');
            $table->string('slug', 255)
                ->index()
                ->unique()
                ->comment('Slug para el URL');
            $table->string('description', 1023)
                ->nullable()
                ->comment('Descripción breve de la sección');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sections');
    }
}
