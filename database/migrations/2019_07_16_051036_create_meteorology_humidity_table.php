<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMeteorologyHumidityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meteorology_humidity', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigIncrements('id');
            $table->decimal('value', 9, 4);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('meteorology_humidity');
    }
}
