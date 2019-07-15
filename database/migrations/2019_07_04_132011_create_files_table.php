<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // id - file_type_id - translation_token - size - originalname - path
        Schema::create('files', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('file_type_id');
            $table->foreign('file_type_id')
                ->references('id')->on('file_types')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedBigInteger('translation_token')->unsigned();
            /*
            $table->foreign('translation_token')
                ->references('token')->on('translations')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            */
            $table->bigInteger('size');
            $table->string('originalname', 511);
            $table->text('path');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files', function (Blueprint $table) {
            $table->dropForeign(['file_type_id']);
            $table->dropForeign(['translation_token']);
        });
    }
}
