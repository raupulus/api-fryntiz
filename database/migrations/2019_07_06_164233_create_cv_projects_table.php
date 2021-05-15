<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCvProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /*
         * id - image_id - translation_title_token -
         * translation_description_token - translation_info_token - url -
         * urlinfo - repositorie_id - repository
         */
        Schema::create('cv_projects', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('image_id');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->unsignedBigInteger('repository_id')->nullable();
            $table->foreign('repository_id')
                ->references('id')->on('cv_repositories')
                ->onUpdate('cascade')
                ->onDelete('set null');
            $table->text('url');
            $table->text('urlinfo');
            $table->text('repository');
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
            $table->dropForeign(['repository_id']);
        });
    }
}
