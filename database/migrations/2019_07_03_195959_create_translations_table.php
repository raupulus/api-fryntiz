<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('language_id');
            $table->foreign('language_id')
                  ->references('id')->on('languages')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');
            $table->string('token', 512)->unique()->index();
            $table->text('text');
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
        Schema::dropIfExists('translations', function (Blueprint $table) {
            $table->dropForeign(['language_id']);
            $table->dropUnique(['token']);
        });
    }
}
