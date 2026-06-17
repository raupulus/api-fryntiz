<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateCvProjectsTable
 */
class CreateCvProjectCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cv_project_categories', function (Blueprint $table) {
            $table->comment('Almacena los datos del apartado de project categories para la generación del currículum vitae de los usuarios.');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único autoincremental de este registro en la base de datos.');
            $table->unsignedBigInteger('project_id')
                ->comment('Relación con el proyecto del curriculum');
            $table->foreign('project_id')
                ->references('id')->on('cv_projects')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE')->comment('Clave foránea que relaciona este registro con el project al que pertenece.');
            $table->unsignedBigInteger('category_id')
                ->comment('Relación con la categoría');
            $table->foreign('category_id')
                ->references('id')->on('categories')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE')->comment('Clave foránea que relaciona este registro con el category al que pertenece.');

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
        Schema::dropIfExists('cv_project_categories', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['category_id']);
        });
    }
}
