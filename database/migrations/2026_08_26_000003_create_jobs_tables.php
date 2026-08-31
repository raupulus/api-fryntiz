<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas de la cola `database`.
 *
 * Existía `failed_jobs` pero no `jobs`, así que el driver `database` no se podía
 * usar y `QUEUE_CONNECTION` estaba en `sync`: cada job se ejecutaba **dentro de
 * la petición**. En la práctica eso significaba que cada visita a un contenido
 * hacía un UPDATE (y a veces un INSERT y un reintento) antes de responder, y que
 * enviar el correo de verificación de la newsletter dejaba al visitante esperando
 * al servidor de correo — y le devolvía un error si el SMTP estaba caído.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->comment('Cola de trabajos pendientes (driver database).');
                $table->id();
                $table->string('queue')->index()->comment('Nombre de la cola.');
                $table->longText('payload')->comment('Job serializado.');
                $table->unsignedTinyInteger('attempts')->comment('Intentos consumidos.');
                $table->unsignedInteger('reserved_at')->nullable()->comment('Momento en que un worker lo reservó.');
                $table->unsignedInteger('available_at')->comment('Momento a partir del cual se puede ejecutar.');
                $table->unsignedInteger('created_at')->comment('Momento en que se encoló.');
            });
        }

        if (! Schema::hasTable('job_batches')) {
            Schema::create('job_batches', function (Blueprint $table) {
                $table->comment('Lotes de trabajos (Bus::batch).');
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
    }
};
