<?php

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
            $table->comment('Tabla para almacenar información de users');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único');
            $table->unsignedBigInteger('role_id')
                ->default(3)
                ->comment('Role principal del usuario, aunque pueda tener otros roles extras');
            $table->foreign('role_id')
                ->references('id')->on('user_roles')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');
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
            $table->text('profile_photo_path')->nullable()->comment('Columna profile photo path');
            $table->string('email')
                ->unique()
                ->comment('Email del usuario, ha de ser único para permitir el login en la aplicación');
            $table->timestamp('email_verified_at')
                ->nullable()
                ->comment('Momento en el que ha verificado el email');
            $table->string('password')
                ->comment('Contraseña del usuario cifrada.');
            $table->text('two_factor_secret')->nullable()->comment('Columna two factor secret');
            $table->text('two_factor_recovery_codes')->nullable()->comment('Columna two factor recovery codes');
            $table->rememberToken()->comment('Token de sesión para recordar usuario');
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
        Schema::dropIfExists('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
        });
    }
}
