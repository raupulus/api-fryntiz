<?php

declare(strict_types=1);

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
            $table->comment('Almacena los registros correspondientes a file types para su integración y uso general en el sistema.');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único autoincremental de este registro en la base de datos.');
            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->comment('Usuario asociado');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE')->comment('Clave foránea que relaciona este registro con el user al que pertenece.');
            $table->string('type', 127)
                ->comment('Tipo de archivo');
            $table->string('mime', 127)
                ->index()
                ->comment('Tipo mime que representa el tipo de archivo');
            $table->string('extension', 12)
                ->comment('Extensión con la que se representa de forma mayoritaria.');
            $table->text('icon16')->nullable()->comment('Campo que almacena el icon16 específico para este registro según la lógica de negocio.');
            $table->text('icon32')->nullable()->comment('Campo que almacena el icon32 específico para este registro según la lógica de negocio.');
            $table->text('icon64')->nullable()->comment('Campo que almacena el icon64 específico para este registro según la lógica de negocio.');
            $table->text('icon128')->nullable()->comment('Campo que almacena el icon128 específico para este registro según la lógica de negocio.');
            $table->timestamps()->comment('Marcas de tiempo utilizadas por Eloquent para llevar el registro de creación y última actualización.');
            $table->softDeletes()->comment('Marca de tiempo empleada por Eloquent para habilitar el borrado lógico (soft deletes).');
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
