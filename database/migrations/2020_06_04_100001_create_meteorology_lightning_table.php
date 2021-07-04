<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateMeteorologyLightningTable
 */
class CreateMeteorologyLightningTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meteorology_lightning', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->comment('Usuario asociado');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->integer('distance')
                ->comment('Distancia estimada de la caída del rayo');
            $table->integer('energy')
                ->comment('Energía detectada para detectar el rayo');
            $table->integer('noise_floor')
                ->nullable()
                ->comment('Ruido de fondo compensado para diferenciar el rayo');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('meteorology_lightning', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }
}
