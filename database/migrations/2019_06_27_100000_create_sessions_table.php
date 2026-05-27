<?php

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
            $table->comment('Tabla para almacenar información de sessions');
            $table->string('id')->primary()->comment('Identificador único');
            $table->foreignId('user_id')->nullable()->index()->comment('Identificador del usuario asociado');
            $table->string('ip_address', 45)->nullable()->comment('Columna ip address');
            $table->text('user_agent')->nullable()->comment('Navegador o agente de usuario');
            $table->text('payload')->comment('Datos o carga útil');
            $table->integer('last_activity')->index()->comment('Columna last activity');
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
