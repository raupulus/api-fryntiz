<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateReferredPlatformsTable
 *
 * Tabla para almacenar las plataformas de referidos disponibles.
 */
class CreateReferredPlatformsTable extends Migration
{
    private $tableName = 'referred_platforms';

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
            $table->unsignedBigInteger('image_id')
                ->nullable()
                ->comment('Relación con la imagen asociada');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');
            $table->string('name', '255')
                ->unique()
                ->comment('Nombre de la plataforma para afiliados.');
            $table->string('slug', '255')
                ->unique()
                ->comment('Nombre de identificación amigable para la plataforma.');
            $table->text('description')
                ->nullable()
                ->comment('Descripción de la plataforma.');
            $table->string('url', '255')
                ->nullable()
                ->comment('Enlace a la página principal.');
            $table->string('url_panel')
                ->nullable()
                ->comment('Enlace al panel de control para referidos.');
            $table->string('url_register')
                ->nullable()
                ->comment('Enlace a la página de registro.');
            $table->timestamps();
            $table->softDeletes();
        });

        $comment = 'Tabla para almacenar las plataformas de referidos.';

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
            $table->dropForeign(['image_id']);
        });
    }
}
