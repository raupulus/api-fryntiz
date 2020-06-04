<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExperienceOthersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // id - translation_name_token - translation_description_token
        Schema::create('experience_others', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
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
        Schema::dropIfExists('experience_others', function (Blueprint $table) {
            $table->dropForeign(['translation_name_token']);
            $table->dropForeign(['translation_description_token']);
        });
    }
}
