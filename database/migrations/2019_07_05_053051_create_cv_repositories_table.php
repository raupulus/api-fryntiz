<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCvRepositoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cv_repositories', function (Blueprint $table) {
            $table->comment('Almacena los datos del apartado de repositories para la generación del currículum vitae de los usuarios.');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único autoincremental de este registro en la base de datos.');
            $table->unsignedBigInteger('curriculum_id')
                ->comment('Relación con el curriculum');
            $table->foreign('curriculum_id')
                ->references('id')->on('cv')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE')->comment('Clave foránea que relaciona este registro con el curriculum al que pertenece.');
            $table->unsignedBigInteger('image_id')
                ->nullable()
                ->comment('Relación con la imagen asociada');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL')->comment('Clave foránea que relaciona este registro con el image al que pertenece.');
            $table->unsignedBigInteger('repository_type_id')
                ->nullable()
                ->comment('Relación con el tipo de repositorios');
            $table->foreign('repository_type_id')
                ->references('id')->on('cv_available_repository_types')
                ->onUpdate('cascade')
                ->onDelete('SET NULL')->comment('Clave foránea que relaciona este registro con el repository type al que pertenece.');
            $table->text('url')
                ->comment('Dirección al repositorio');
            $table->string('title', 511)
                ->comment('Título para el repositorio');
            $table->text('description')
                ->nullable()
                ->comment('Descripción del repositorio');
            $table->string('name', 255)
                ->comment('Nombre del repositorio');
            $table->timestamps()->comment('Marcas de tiempo utilizadas por Eloquent para llevar el registro de creación y última actualización.');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cv_repositories', function (Blueprint $table) {
            $table->dropForeign(['image_id']);
            $table->dropForeign(['curriculum_id']);
        });
    }
}
