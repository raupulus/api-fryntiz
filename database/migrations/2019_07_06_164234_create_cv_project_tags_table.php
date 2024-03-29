<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateCvProjectsTable
 */
class CreateCvProjectTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cv_project_tags', function (Blueprint $table) {
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
            $table->unsignedBigInteger('tag_id')
                ->comment('Relación con la etiqueta');
            $table->foreign('tag_id')
                ->references('id')->on('tags')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
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
        Schema::dropIfExists('cv_project_tags', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['category_id']);
        });
    }
}
