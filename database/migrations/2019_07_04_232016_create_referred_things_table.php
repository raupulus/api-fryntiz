<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateReferredThingsTable
 *
 * Tabla para almacenar las cosas referidas de programas de afiliación.
 */
class CreateReferredThingsTable extends Migration
{
    private $tableName = 'referred_things';

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
            $table->unsignedBigInteger('image_id')
                ->nullable()
                ->comment('Relación con la imagen asociada');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');
            $table->string('name', '255')
                ->unique()
                ->comment('Nombre de esa cosa afiliada.');
            $table->text('description')
                ->nullable()
                ->comment('Descripción de la cosa referida.');
            $table->string('url', '255')
                ->nullable()
                ->comment('URL de la cosa referida.');

            $table->timestamps();
            $table->softDeletes();
        });

        $comment = 'Tabla para almacenar las cosas referidas de programas de afiliación.';

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
            $table->dropForeign(['image_id']);
        });
    }
}
