<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('curriculum_id')
                ->comment('Relación con el curriculum');
            $table->foreign('curriculum_id')
                ->references('id')->on('cv')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->unsignedBigInteger('image_id')
                ->nullable()
                ->comment('Relación con la imagen asociada');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');
            $table->unsignedBigInteger('repository_type')
                ->nullable()
                ->comment('Relación con el tipo de repositorios');
            $table->foreign('repository_type')
                ->references('id')->on('cv_available_repository_types')
                ->onUpdate('cascade')
                ->onDelete('SET NULL');
            $table->text('url')
                ->comment('Dirección al repositorio');
            $table->string('title', 511)
                ->comment('Título para el repositorio');
            $table->text('description')
                ->nullable()
                ->comment('Descripción del repositorio');
            $table->string('name', 255)
                ->comment('Nombre del repositorio');
            $table->timestamps();
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
