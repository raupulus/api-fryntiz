<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateUsersTable
 */
class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->comment('Almacena los usuarios registrados en el sistema, habilitando la autenticación, gestión de roles y relación con entidades.');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único autoincremental de este registro en la base de datos.');
            $table->unsignedBigInteger('role_id')
                ->default(3)
                ->comment('Role principal del usuario, aunque pueda tener otros roles extras');
            // RESTRICT y no SET NULL: la columna es NOT NULL con default 3, así
            // que un SET NULL sobre ella fallaba en tiempo de ejecución. Borrar
            // un rol que aún tiene usuarios tiene que dar error, no dejar filas
            // inconsistentes.
            $table->foreign('role_id')
                ->references('id')->on('user_roles')
                ->onUpdate('CASCADE')
                ->onDelete('RESTRICT');
            $table->boolean('is_active')
                ->default(true)
                ->comment('Indica si la cuenta está activa. Distinto de deleted_at, que es borrado lógico.');
            $table->foreignId('current_team_id')
                ->nullable()
                ->comment('Identificador del equipo al que pertenece.');
            $table->string('name')
                ->comment('Nombre del usuario');
            $table->string('surname')
                ->nullable()
                ->comment('Apellidos del usuario');
            $table->string('nickname', 511)
                ->nullable()
                ->unique()
                ->comment('Apodo del usuario, ha de ser único para permitir el login en la aplicación');
            $table->text('profile_photo_path')->nullable()->comment('Ruta en el sistema de archivos o enlace donde reside la imagen de perfil asociada.');
            $table->string('email')
                ->unique()
                ->comment('Email del usuario, ha de ser único para permitir el login en la aplicación');
            $table->timestamp('email_verified_at')
                ->nullable()
                ->comment('Momento en el que ha verificado el email');
            $table->string('password')
                ->comment('Contraseña del usuario cifrada.');
            $table->text('two_factor_secret')->nullable()->comment('Clave o código secreto empleado de manera interna para soportar el factor de doble autenticación.');
            $table->text('two_factor_recovery_codes')->nullable()->comment('Clave o código secreto empleado de manera interna para soportar el factor de doble autenticación.');
            $table->rememberToken()->comment('Token de sesión para recordar usuario');
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
        Schema::dropIfExists('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
        });
    }
}
