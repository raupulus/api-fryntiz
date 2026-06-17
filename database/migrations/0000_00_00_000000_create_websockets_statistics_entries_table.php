<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebSocketsStatisticsEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('websockets_statistics_entries', function (Blueprint $table) {
            $table->comment('Almacena los registros correspondientes a websockets statistics entries para su integración y uso general en el sistema.');
            $table->increments('id')->comment('Identificador único autoincremental de este registro en la base de datos.');
            $table->string('app_id')->comment('Identificador asociado a app');
            $table->integer('peak_connection_count')->comment('Campo que almacena el peak connection count específico para este registro según la lógica de negocio.');
            $table->integer('websocket_message_count')->comment('Campo que almacena el websocket message count específico para este registro según la lógica de negocio.');
            $table->integer('api_message_count')->comment('Campo que almacena el api message count específico para este registro según la lógica de negocio.');
            $table->nullableTimestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('websockets_statistics_entries');
    }
}
