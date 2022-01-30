<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateReferredPlatformsTable
 *
 * Tabla para almacenar los usuarios asociados a plataformas de referidos.
 */
class CreateReferredUsersTable extends Migration
{
    private $tableName = 'referred_users';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')
                ->comment('Relación con el usuario');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->unsignedBigInteger('referred_platform_id')
                ->comment('Relación con la plataforma de referido');
            $table->foreign('referred_platform_id')
                ->references('id')->on('referred_platforms')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->string('id_affiliated', '255')
                ->unique()
                ->comment('El id de afiliado dentro de la plataforma.');
            $table->string('token', '255')
                ->unique()
                ->comment('Token para conectar con la plataforma.');
            $table->date('start_at')
                ->nullable()
                ->comment('Fecha de inicio de la afiliación.');
            $table->timestamps();
            $table->softDeletes();
        });

        $comment = 'Tabla para almacenar los usuarios asociados a plataformas de referidos.';

        DB::statement("COMMENT ON TABLE {$this->tableName} IS '{$comment}'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->tableName, function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['referred_platform_id']);
        });
    }
}
