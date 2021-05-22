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
            $table->string('search_image', 511)->nullable();
            $table->string('search_title', 511)->nullable();
            $table->string('search_description', 511)->nullable();
            $table->string('meta_author', 511)->nullable();
            $table->string('meta_distribution', 255)
                ->default('global')
                ->nullable();
            $table->string('meta_description', 511)->nullable();
            $table->string('meta_keywords', 511)->nullable();
            $table->string('meta_robots', 127)
                ->default('index, follow')
                ->nullable();
            $table->string('meta_copyright', 511)->nullable();
            $table->string('og_title', 511)->nullable();
            $table->string('og_description', 511)->nullable();
            $table->string('og_type', 511)->nullable();
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
