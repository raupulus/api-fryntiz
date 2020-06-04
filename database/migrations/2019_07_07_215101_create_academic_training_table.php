<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAcademicTrainingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**
         * id - image_id - translation_name_token - translation_rank_token -
         * translation_description_token - entity - date_obtaining - date_start
         * - date_end
         */
        Schema::create('academic_training', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('image_id');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->unsignedBigInteger('translation_name_token');
            /*
            $table->foreign('translation_name_token')
                ->references('token')->on('translations')
                ->onUpdate('cascade')
                ->onDelete('no action');
            */
            $table->unsignedBigInteger('translation_rank_token');
            /*
            $table->foreign('translation_rank_token')
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
            $table->string('entity', 511);
            $table->dateTime('date_obtaining');
            $table->dateTime('date_start');
            $table->dateTime('date_end');
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
        Schema::dropIfExists('academic_training', function (Blueprint $table) {
            $table->dropForeign(['image_id']);
            $table->dropForeign(['translation_name_token']);
            $table->dropForeign(['translation_rank_token']);
            $table->dropForeign(['translation_description_token']);
        });
    }
}
