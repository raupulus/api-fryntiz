<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // id - image_id - translation_token - name - url
        Schema::create('services', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('image_id');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->unsignedBigInteger('translation_token');
            /*
            $table->foreign('translation_token')
                ->references('token')->on('translations')
                ->onUpdate('cascade')
                ->onDelete('no action');
            */
            $table->string('name', 511);
            $table->text('url');
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
        Schema::dropIfExists('services', function (Blueprint $table) {
            $table->dropForeign(['image_id']);
            $table->dropForeign(['translation_token']);
        });
    }
}
