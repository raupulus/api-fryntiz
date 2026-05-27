<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateUserRolesTable
 */
class CreateUserRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->comment('Tabla para almacenar información de user roles');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único');
            $table->string('name', '255')
                ->unique()
                ->comment('Nombre para control del role en permisos.');
            $table->string('display_name', '255')
                ->unique()
                ->comment('Nombre a mostrar');
            $table->string('slug', '255')
                ->unique()
                ->comment('Nombre interno del role.');
            $table->text('description')
                ->nullable()
                ->comment('Descripción del funcionamiento del role.');
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
        Schema::dropIfExists('user_roles');
    }
}
