<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateCvProjectsTable
 */
class CreateCvProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cv_projects', function (Blueprint $table) {
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
                ->comment('Relación con la imagen');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            /*
            $table->unsignedBigInteger('repository_id')->nullable();
            $table->foreign('repository_id')
                ->references('id')->on('cv_repositories')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            */
            $table->string('title', 511)
                ->comment('Título de la colaboración');
            $table->text('description')
                ->nullable()
                ->comment('Descripción del proyecto');
            $table->string('url', 511)
                ->nullable()
                ->comment('Url principal hacia el sitio oficial');
            $table->string('urlinfo', 511)
                ->nullable()
                ->comment('Url de información sobre el proyecto');
            $table->text('repository')
                ->nullable()
                ->comment('Url del repositorio');

            $table->string('role', 255)
                ->nullable()
                ->comment('Rol en el proyecto');
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
        Schema::dropIfExists('cv_projects', function (Blueprint $table) {
            $table->dropForeign(['image_id']);
            $table->dropForeign(['curriculum_id']);
            //$table->dropForeign(['repository_id']);
        });
    }
}
