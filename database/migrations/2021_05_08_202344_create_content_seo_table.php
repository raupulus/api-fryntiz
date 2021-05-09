<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateContentSeoTable
 */
class CreateContentSeoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('content_seo', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->bigInteger('content_id')
                ->index()
                ->nullable()
                ->comment('FK al contenido asociado');
            $table->foreign('content_id')
                ->references('id')->on('contents')
                ->onUpdate('cascade')
                ->onDelete('set null');
            $table->bigInteger('language_id')
                ->index()
                ->nullable()
                ->comment('FK al idioma asociado');
            $table->foreign('language_id')
                ->references('id')->on('languages')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->string('author', 511)->nullable();
            $table->string('description', 511)->nullable();
            $table->string('keywords', 511)->nullable();
            $table->string('robots', 127)->nullable();
            $table->string('copyright', 511)->nullable();
            $table->string('og_title', 511)->nullable();
            $table->string('og_site_name', 511)->nullable();
            $table->string('og_description', 511)->nullable();
            $table->string('og_image', 511)->nullable();
            $table->string('og_image_alt', 511)->nullable();
            $table->string('twitter_card', 511)->nullable();
            $table->string('twitter_site', 511)->nullable();
            $table->string('twitter_creator', 511)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('content_seo', function (Blueprint $table) {
            $table->dropForeign(['content_id']);
            $table->dropForeign(['language_id']);
        });
    }
}
