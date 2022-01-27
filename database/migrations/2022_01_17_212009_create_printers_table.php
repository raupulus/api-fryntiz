<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreatePrintersTable
 */
class CreatePrintersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('printers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')
                ->comment('Relación con el usuario propietario de la impresora');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->unsignedBigInteger('printer_type_id')
                ->comment('Relación el tipo de impresora');
            $table->foreign('printer_type_id')
                ->references('id')->on('printer_types')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->string('name', 511)
                ->comment('Nombre del tipo de impresora');
            $table->string('code', 255)
                ->nullable()
                ->comment('Código identificador de la impresora');
            $table->text('description')
                ->nullable()
                ->comment('Descripción');
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
        Schema::dropIfExists('printers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['printer_type_id']);
        });
    }
}
