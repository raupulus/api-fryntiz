<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateContentSeoTable
 */
class CreateContentSeoTable extends Migration
{
    private $tableName = 'content_seo';
    private $tableComment = 'Información para SEO';

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
                //->nullable()
                ->comment('FK al contenido asociado');
            $table->foreign('content_id')
                ->references('id')->on('contents')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->bigInteger('image_id')
                ->index()
                ->nullable()
                ->comment('FK a la imagen asociada');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->string('image_alt', 255)
                ->nullable()
                ->comment('Título alternativo para la imagen de buscadores y redes sociales');

            $table->enum('distribution', ['global', 'local', 'ui'])
                ->default('global')
                ->comment('Distribución del contenido');

            $table->string('keywords', 511)
                ->nullable()
                ->comment('Palabras clave para SEO');

            $table->string('revisit_after', 32)
                ->default('7 days')
                ->nullable()
                ->comment('Sugiere a los motores de búsqueda que vuelvan a indexar la página después de un tiempo determinado');

            $table->string('description', 255)
                ->nullable()
                ->comment('Descripción');

            $table->string('robots', 30)
                ->default('index, follow')
                ->nullable();

            $table->string('og_title', 255)
                ->nullable()
                ->comment('Título para Open Graph - Redes sociales');

            $table->string('og_type', 50)
                ->default('website')
                ->comment('Tipo de contenido para Open Graph - Redes sociales');

            $table->string('twitter_card', 50)
                ->default('summary')
                ->nullable()
                ->comment('Tipo de tarjeta para Twitter - Redes sociales');
            $table->string('twitter_creator', 255)
                ->nullable()
                ->comment('Creador para Twitter - Redes sociales');

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
            $table->dropForeign(['language_id']);
        });
    }
}
