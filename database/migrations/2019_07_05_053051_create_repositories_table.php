<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRepositoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // id - image_id - url - translation_token (fg languages) - title - description - name - perfil - alt
        Schema::create('repositories', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('image_id');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->text('url');
            $table->unsignedBigInteger('translation_token');
            /*
            $table->foreign('translation_token')
                ->references('token')->on('translations')
                ->onUpdate('cascade')
                ->onDelete('no action');
            */
            $table->string('title', 511);
            $table->string('name', 255);
            $table->string('perfil', 255);
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
        Schema::dropIfExists('repositories', function (Blueprint $table) {
            $table->dropForeign(['image_id']);
            $table->dropForeign(['translation_token']);
        });
    }
}
