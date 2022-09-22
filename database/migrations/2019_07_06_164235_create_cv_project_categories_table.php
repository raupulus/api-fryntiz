<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('project_id')
                ->comment('Relación con el proyecto del curriculum');
            $table->foreign('project_id')
                ->references('id')->on('cv_projects')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->unsignedBigInteger('category_id')
                ->comment('Relación con la categoría');
            $table->foreign('category_id')
                ->references('id')->on('categories')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            // TODO → Agregar tabla de categories y relacionar con ella

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
        Schema::dropIfExists('cv_project_categories', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['category_id']);
        });
    }
}
