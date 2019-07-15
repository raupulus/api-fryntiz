<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSkillsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**
         * id - image_id - skill_type_id - translation_name_token -
         * translation_description_token - level (1-100)
         */
        Schema::create('skills', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('image_id');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->unsignedBigInteger('skill_type_id');
            $table->foreign('skill_type_id')
                ->references('id')->on('skills_type')
                ->onUpdate('cascade')
                ->onDelete('set null');
            $table->unsignedBigInteger('translation_name_token');
            /*
            $table->foreign('translation_name_token')
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
            $table->integer('level');
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
        Schema::dropIfExists('skills', function (Blueprint $table) {
            $table->dropForeign(['skill_type_id']);
            $table->dropForeign(['translation_name_token']);
            $table->dropForeign(['translation_description_token']);
        });
    }
}
