<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateGalleryImagesTable
 */
class CreateGalleryImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gallery_images', function (Blueprint $table) {
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
            $table->bigInteger('image_id')
                ->nullable()
                ->comment('FK a la imagen en la tabla files');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['gallery_id', 'image_id']);
            $table->index(['gallery_id', 'image_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gallery_images', function (Blueprint $table) {
            $table->dropForeign(['image_id']);
            $table->dropForeign(['gallery_id']);
        });
    }
}
