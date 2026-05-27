<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFailedJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->comment('Tabla para almacenar información de failed jobs');
            $table->id()->comment('Identificador único');
            $table->string('uuid')->unique()->comment('Identificador universalmente único');
            $table->text('connection')->comment('Columna connection');
            $table->text('queue')->comment('Columna queue');
            $table->longText('payload')->comment('Datos o carga útil');
            $table->longText('exception')->comment('Detalle de la excepción');
            $table->timestamp('failed_at')->useCurrent()->comment('Columna failed at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('failed_jobs');
    }
}
