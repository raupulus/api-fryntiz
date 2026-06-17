<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContentDailyViewsTable extends Migration
{
    public function up()
    {
        Schema::create('content_daily_views', function (Blueprint $table) {
            $table->comment('Almacena la información de daily views requerida por el sistema multiplataforma de gestión de contenidos (CMS).');
            $table->id()->comment('Identificador único autoincremental de este registro en la base de datos.');
            $table->unsignedBigInteger('content_id')->comment('Clave foránea que relaciona este registro con el content al que pertenece.');
            $table->date('date')->comment('Fecha');
            $table->unsignedInteger('views')->default(0)->comment('Campo que almacena el views específico para este registro según la lógica de negocio.');
            $table->timestamps()->comment('Marcas de tiempo utilizadas por Eloquent para llevar el registro de creación y última actualización.');

            // Índices optimizados
            $table->unique(['content_id', 'date']);
            $table->index(['date', 'views']); // Para consultas de "más vistos por período"
            $table->index('content_id');

            $table->foreign('content_id')->references('id')->on('contents')->comment('Clave foránea que relaciona este registro con el content al que pertenece.');
        });
    }

    public function down()
    {
        Schema::dropIfExists('content_daily_views');
    }
}
