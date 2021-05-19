<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateUsersSocialTable
 */
class CreateUsersSocialTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_social', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedBigInteger('social_network_id');
            $table->foreign('social_network_id')
                ->references('id')->on('social_networks')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('nick')
                ->index()
                ->nullable()
                ->comment('Nick o usuario dentro de la red social');
            $table->string('url');
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
        Schema::dropIfExists('user_social', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['social_networks']);
        });
    }
}
