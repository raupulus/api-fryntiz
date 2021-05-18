<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateUsersRolesTable
 */
class CreateUsersRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
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
        Schema::dropIfExists('user_roles');
    }
}
