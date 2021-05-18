<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateContentGalleriesTable
 */
class CreateContentGalleriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('content_galleries', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->bigInteger('gallery_id')
                ->nullable()
                ->comment('FK al a la galería que pertenezca');
            $table->foreign('gallery_id')
                ->references('id')->on('galleries')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->bigInteger('content_id')
                ->nullable()
                ->comment('FK al contenido que se asocia con la galería');
            $table->foreign('content_id')
                ->references('id')->on('contents')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            //$table->unique(['gallery_id', 'content_id']);
            //$table->index(['gallery_id', 'content_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('content_galleries', function (Blueprint $table) {
            $table->dropForeign(['gallery_id']);
            $table->dropForeign(['content_id']);
        });
    }
}
