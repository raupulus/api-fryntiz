<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSmartbonsaiPlantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('smartbonsai_plants', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->string('name')
                ->comment('Nombre común de la planta');
            $table->string('name_scientific')
                ->comment('Nombre científico de la planta');
            $table->string('description')
                ->comment('Descripción general de la planta');
            $table->text('details')
                ->comment('Descripción avanzada con detalles de la planta');
            $table->text('image')
                ->nullable()
                ->default('smartplant/default.jpg')
                ->comment('Imagen que representa a la planta');
            $table->timestamp('start_at')
                ->comment('Momento en el que se ha sembrado');
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
        Schema::dropIfExists('smartbonsai_plants');
    }
}
