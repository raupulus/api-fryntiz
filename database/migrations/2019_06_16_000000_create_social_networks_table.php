<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateSocialNetworksTable
 */
class CreateSocialNetworksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('social_networks', function (Blueprint $table) {
            $table->comment('Almacena los registros correspondientes a social networks para su integración y uso general en el sistema.');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único autoincremental de este registro en la base de datos.');
            $table->string('name', 255)
                ->unique()
                ->comment('Nombre de la red social');
            $table->string('slug', 255)
                ->unique()
                ->index()
                ->comment('Slug para la red social');
            $table->string('type', 255)
                ->comment('Tipo de red social');
            $table->string('color', 255)
                ->comment('Código Hexadecimal del color primario de la red social');
            $table->text('url')
                ->comment('Url a la página principal de la red social');
            $table->text('url_user')
                ->nullable()
                ->comment('Parte de la url hacia el perfil de usuario');
            $table->text('url_privacity')
                ->nullable()
                ->comment('Url a la política de privacidad de la red social');
            $table->string('icon', 255)
                ->nullable()
                ->comment('Icono para la red social');
            $table->text('image')
                ->nullable()
                ->comment('Imagen de la red social a 120x120px');
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
        Schema::dropIfExists('social_networks');
    }
}
