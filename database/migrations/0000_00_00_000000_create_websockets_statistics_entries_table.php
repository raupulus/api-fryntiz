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
            $table->comment('Tabla para almacenar información de websockets statistics entries');
            $table->increments('id')->comment('Identificador único');
            $table->string('app_id')->comment('Identificador asociado a app');
            $table->integer('peak_connection_count')->comment('Columna peak connection count');
            $table->integer('websocket_message_count')->comment('Columna websocket message count');
            $table->integer('api_message_count')->comment('Columna api message count');
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
