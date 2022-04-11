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
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
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
            $table->text('profile_photo_path')->nullable();
            $table->string('email')
                ->unique()
                ->comment('Email del usuario, ha de ser único para permitir el login en la aplicación');
            $table->timestamp('email_verified_at')
                ->nullable()
                ->comment('Momento en el que ha verificado el email');
            $table->string('password')
                ->comment('Contraseña del usuario cifrada.');
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
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
