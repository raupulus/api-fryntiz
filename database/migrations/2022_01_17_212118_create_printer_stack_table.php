<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreatePrinterStackTable
 */
class CreatePrinterStackTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('printer_stack', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')
                ->comment('Relación con el usuario que creó el registro');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->unsignedBigInteger('printer_id')
                ->comment('Relación la impresora');
            $table->foreign('printer_id')
                ->references('id')->on('printers')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->string('note', 511)
                ->nullable()
                ->comment('Notas sobre la impresión');
            $table->text('content')
                ->nullable()
                ->comment('content');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('printer_stack', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['printer_id']);
        });
    }
}
