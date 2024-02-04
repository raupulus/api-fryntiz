<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateSectionsTable
 *
 * Tabla para distinguir en secciones cada tipo de contenido, por ejemplo el que
 * se crea para una web de informática, para el curriculum, para los vuelos,
 * para la estación meteorológica...
 *
 */
class CreatePlatformsTable extends Migration
{
    private $tableName = 'platforms';
    private $tableComment = 'Almacena las distintas plataformas en las que se agruparán los contenidos';

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
            $table->unsignedBigInteger('image_id')
                ->nullable()
                ->comment('Relación con la imagen asociada');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');
            $table->string('title', 511)
                ->index()
                ->comment('Título de la sección');
            $table->string('slug', 255)
                ->index()
                ->unique()
                ->comment('Slug para el URL');
            $table->string('description', 1023)
                ->nullable()
                ->comment('Descripción breve de la sección');
            $table->string('domain', 255)
                ->nullable()
                ->comment('Dominio principal hacia la plataforma');
            $table->string('url_about', 255)
                ->nullable()
                ->comment('Página con información del proyecto');
            $table->string('youtube_channel_id', 64)
                ->nullable()
                ->comment('Identificador del canal en youtube');
            $table->string('youtube_presentation_video_id', 64)
                ->nullable()
                ->comment('Vídeo principal con la presentación del proyecto en youtube');
            $table->string('twitter', 255)
                ->nullable()
                ->comment('Usuario en twitter');
            $table->string('twitter_token', 511)
                ->nullable()
                ->comment('Token para la api de twitter');
            $table->string('mastodon', 255)
                ->nullable()
                ->comment('Usuario en mastodon');
            $table->string('mastodon_token', 255)
                ->nullable()
                ->comment('Token para la api de mastodon');
            $table->string('twitch', 255)
                ->nullable()
                ->comment('Usuario en twitch');
            $table->string('tiktok', 255)
                ->nullable()
                ->comment('Usuario en tiktok');
            $table->string('instagram', 255)
                ->nullable()
                ->comment('Usuario en instagram');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement("COMMENT ON TABLE {$this->tableName} IS '{$this->tableComment}'");
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
            $table->dropForeign(['image_id']);
        });
    }
}
