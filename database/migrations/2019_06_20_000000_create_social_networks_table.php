<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
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
                ->comment('Parte de la url hacia el perfil de usuario');
            $table->text('url_privacity')
                ->comment('Url a la política de privacidad de la red social');
            $table->string('icon', 255)
                ->comment('Icono para la red social');
            $table->text('image')
                ->comment('Imagen de la red social a 120x120px');
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
        Schema::dropIfExists('social_networks');
    }
}
