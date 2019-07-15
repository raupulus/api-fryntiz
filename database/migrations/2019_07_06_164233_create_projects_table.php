<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectsTable extends Migration
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
        Schema::create('projects', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('image_id');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->unsignedBigInteger('repositorie_id')->nullable();
            $table->foreign('repositorie_id')
                ->references('id')->on('repositories')
                ->onUpdate('cascade')
                ->onDelete('set null');
            $table->unsignedBigInteger('translation_title_token');
            /*
            $table->foreign('translation_title_token')
                ->references('token')->on('translations')
                ->onUpdate('cascade')
                ->onDelete('no action');
            */
            $table->unsignedBigInteger('translation_description_token');
            /*
            $table->foreign('translation_description_token')
                ->references('token')->on('translations')
                ->onUpdate('cascade')
                ->onDelete('no action');
            */
            $table->unsignedBigInteger('translation_info_token');
            /*
            $table->foreign('translation_info_token')
                ->references('token')->on('translations')
                ->onUpdate('cascade')
                ->onDelete('no action');
            */
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
        Schema::dropIfExists('projects', function (Blueprint $table) {
            $table->dropForeign(['image_id']);
            $table->dropForeign(['repositorie_id']);
            $table->dropForeign(['translation_title_token']);
            $table->dropForeign(['translation_description_token']);
            $table->dropForeign(['translation_info_token']);
        });
    }
}
