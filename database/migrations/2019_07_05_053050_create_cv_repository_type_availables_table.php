<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateCvRepositoryTypesTable
 */
class CreateCvRepositoryTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cv_repository_type_availables', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('image_id')
                ->comment('Relación con la imagen asociada');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->unsignedBigInteger('repository_type_availables')
                ->comment('Relación con el tipo de repositorios');
            $table->foreign('repository_type')
                ->references('id')->on('repository_types')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->text('url')
                ->comment('Dirección al repositorio');
            $table->string('title', 511)
                ->comment('Título para el repositorio');
            $table->string('name', 255)
                ->comment('Nomre del repositorio');
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
        });
    }
}
