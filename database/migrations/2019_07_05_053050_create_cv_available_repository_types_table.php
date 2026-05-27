<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateCvRepositoryTypesTable
 */
class CreateCvAvailableRepositoryTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cv_available_repository_types', function (Blueprint $table) {
            $table->comment('Tabla para almacenar información de cv available repository types');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único');
            $table->unsignedBigInteger('image_id')
                ->nullable()
                ->comment('Relación con la imagen asociada');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('cascade')
                ->onDelete('SET NULL');
            $table->string('title', 511)
                ->nullable()
                ->comment('Título para el repositorio');
            $table->string('name', 255)
                ->nullable()
                ->comment('Nombre del repositorio');
            $table->text('slug')
                ->unique()
                ->comment('Identificador único para el repositorio');
            $table->text('url')
                ->nullable()
                ->comment('Dirección al repositorio');
            $table->timestamps()->comment('Marcas de tiempo de creación y actualización');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cv_available_repository_types', function (Blueprint $table) {
            $table->dropForeign(['image_id']);
        });
    }
}
