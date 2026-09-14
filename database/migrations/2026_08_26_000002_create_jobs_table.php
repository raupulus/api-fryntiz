<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cola de trabajos del driver `database`.
 *
 * Existía `failed_jobs` pero no `jobs`, así que el driver `database` no se podía
 * usar y `QUEUE_CONNECTION` estaba en `sync`: cada job se ejecutaba **dentro de
 * la petición**. Cada visita a un contenido hacía un UPDATE antes de responder, y
 * enviar el correo de verificación de la newsletter dejaba al visitante esperando
 * al servidor de correo.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Las bases que ya existían la crearon con la migración que juntaba
        // `jobs` y `job_batches`; ahí esta no tiene nada que hacer.
        if (Schema::hasTable('jobs')) {
            return;
        }

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

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
