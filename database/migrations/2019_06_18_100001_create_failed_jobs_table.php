<?php

declare(strict_types=1);

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
            $table->comment('Trabajos en cola que agotaron los reintentos.');
            $table->id()->comment('Identificador único autoincremental de este registro en la base de datos.');
            $table->string('uuid')->unique()->comment('Identificador universalmente único');
            $table->text('connection')->comment('Conexión de cola en la que corría el trabajo.');
            $table->text('queue')->comment('Nombre de la cola en la que estaba encolado.');
            $table->longText('payload')->comment('Datos o carga útil');
            $table->longText('exception')->comment('Detalle de la excepción');
            $table->timestamp('failed_at')->useCurrent()->comment('Cuándo agotó los reintentos.');
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
