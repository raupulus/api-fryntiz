<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContentDailyViewsTable extends Migration
{
    public function up()
    {
        Schema::create('content_daily_views', function (Blueprint $table) {
            $table->comment('Tabla para almacenar información de content daily views');
            $table->id()->comment('Identificador único');
            $table->unsignedBigInteger('content_id');
            $table->date('date')->comment('Fecha');
            $table->unsignedInteger('views')->default(0);
            $table->timestamps()->comment('Marcas de tiempo de creación y actualización');

            // Índices optimizados
            $table->unique(['content_id', 'date']);
            $table->index(['date', 'views']); // Para consultas de "más vistos por período"
            $table->index('content_id');

            $table->foreign('content_id')->references('id')->on('contents');
        });
    }

    public function down()
    {
        Schema::dropIfExists('content_daily_views');
    }
}
