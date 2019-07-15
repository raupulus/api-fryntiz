<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExperienceSelfEmployeedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**
         * id - image_id - translation_name_token -
         * translation_description_token - position - company - url - date_start
         * - date_end
         */
        Schema::create('experience_self_employeed', function (Blueprint $table) {
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
            $table->unsignedBigInteger('translation_description_token');
            /*
            $table->foreign('translation_description_token')
                ->references('token')->on('translations')
                ->onUpdate('cascade')
                ->onDelete('no action');
            */
            $table->string('position', 255);
            $table->string('company', 511);
            $table->text('url');
            $table->timestamp('date_start');
            $table->timestamp('date_end');
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
        Schema::dropIfExists('experience_self_employeed', function (Blueprint $table) {
            $table->dropForeign(['image_id']);
            $table->dropForeign(['translation_name_token']);
            $table->dropForeign(['translation_description_token']);
        });
    }
}
