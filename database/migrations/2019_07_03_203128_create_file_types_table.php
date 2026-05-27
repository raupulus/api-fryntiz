<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateFileTypesTable
 */
class CreateFileTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('file_types', function (Blueprint $table) {
            $table->comment('Tabla para almacenar información de file types');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único');
            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->comment('Usuario asociado');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->string('type', 127)
                ->comment('Tipo de archivo');
            $table->string('mime', 127)
                ->index()
                ->comment('Tipo mime que representa el tipo de archivo');
            $table->string('extension', 12)
                ->comment('Extensión con la que se representa de forma mayoritaria.');
            $table->text('icon16')->nullable()->comment('Columna icon16');
            $table->text('icon32')->nullable()->comment('Columna icon32');
            $table->text('icon64')->nullable()->comment('Columna icon64');
            $table->text('icon128')->nullable()->comment('Columna icon128');
            $table->timestamps()->comment('Marcas de tiempo de creación y actualización');
            $table->softDeletes()->comment('Marca de tiempo para borrado lógico');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('file_types');
    }
}
