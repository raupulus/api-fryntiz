<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateContentTagsTable
 */
class CreateContentMetadataTable extends Migration
{
    private $tableName = 'content_metadata';
    private $tableComment = 'Metadatos asociados al contenido';

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
            $table->bigInteger('content_id')
                ->index()
                ->nullable()
                ->comment('FK al contenido asociado');
            $table->foreign('content_id')
                ->references('id')->on('contents')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('web')
                ->nullable()
                ->comment('Sitio web del contenido');
            $table->string('telegram_channel')
                ->nullable()
                ->comment('Canal de telegram');
            $table->string('youtube_channel')
                ->nullable()
                ->comment('Canal de Youtube, página principal del canal');
            $table->string('youtube_video')
                ->nullable()
                ->comment('Vídeo en Youtube con presentación o asociado al contenido');
            $table->string('youtube_video_id')
                ->nullable()
                ->comment('ID del vídeo subido en Youtube');
            $table->string('gitlab')
                ->nullable()
                ->comment('Url del repositorio asociado en Gitlab');
            $table->string('github')
                ->nullable()
                ->comment('Url del repositorio asociado en Github');
            $table->string('mastodon')
                ->nullable()
                ->comment('Cuenta de Mastodon');
            $table->string('twitter')
                ->nullable()
                ->comment('Cuenta de Twitter');

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
            $table->dropForeign(['content_id']);
        });
    }
}
