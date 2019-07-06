<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /*
         * id - image_id - translation_title_token -
         * translation_description_token - translation_info_token - url -
         * urlinfo - repository - role
         */
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('image_id');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->string('translation_title_token', 511);
            $table->foreign('translation_title_token')
                ->references('token')->on('translations')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->string('translation_description_token', 511);
            $table->foreign('translation_description_token')
                ->references('token')->on('translations')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->string('translation_info_token', 511);
            $table->foreign('translation_info_token')
                ->references('token')->on('translations')
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->text('url');
            $table->text('urlinfo');
            $table->text('repository');
            $table->string('role', 255);
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
        Schema::dropIfExists('jobs', function (Blueprint $table) {
            $table->dropForeign(['image_id']);
            $table->dropForeign(['translation_title_token']);
            $table->dropForeign(['translation_description_token']);
            $table->dropForeign(['translation_info_token']);
        });
    }
}
