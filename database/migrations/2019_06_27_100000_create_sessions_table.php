<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateSessionsTable
 */
class CreateSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->comment('Sesiones abiertas de los usuarios.');
            $table->string('id')->primary()->comment('Identificador único autoincremental de este registro en la base de datos.');
            $table->foreignId('user_id')->nullable()->index()->comment('Identificador del usuario asociado');
            $table->string('ip_address', 45)->nullable()->comment('Dirección IP desde la que se originó la petición o sesión');
            $table->text('user_agent')->nullable()->comment('Navegador o agente de usuario');
            $table->text('payload')->comment('Datos o carga útil');
            $table->integer('last_activity')->index()->comment('Marca de tiempo UNIX de la última actividad registrada.');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sessions');
    }
}
