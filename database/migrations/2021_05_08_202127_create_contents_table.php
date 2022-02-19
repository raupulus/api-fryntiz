<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateContentsTable
 */
class CreateContentsTable extends Migration
{
    private $tableName = 'contents';
    private $tableComment = 'Contenido';

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
            $table->bigInteger('author_id')
                ->index()
                ->nullable()
                ->comment('FK al usuario propietario del post');
            $table->foreign('author_id')
                ->references('id')->on('users')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');
            $table->bigInteger('platform_id')
                ->index()
                ->nullable()
                ->comment('FK a la plataforma que se asocia este contenido');
            $table->foreign('platform_id')
                ->references('id')->on('platforms')
                ->onUpdate('cascade')
                ->onDelete('SET NULL');
            $table->bigInteger('status_id')
                ->index()
                ->nullable()
                ->comment('FK al estado en la tabla content_status');
            $table->foreign('status_id')
                ->references('id')->on('content_available_status')
                ->onUpdate('cascade')
                ->onDelete('SET NULL');
            $table->bigInteger('type_id')
                ->index()
                ->nullable()
                ->comment('FK al tipo de contenido en la tabla content_type');
            $table->foreign('type_id')
                ->references('id')->on('content_available_types')
                ->onUpdate('cascade')
                ->onDelete('SET NULL');
            $table->bigInteger('image_id')
                ->nullable()
                ->comment('FK a la imagen en la tabla files');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('cascade')
                ->onDelete('SET NULL');
            $table->string('title', 511)
                ->index()
                ->comment('Título de la página');
            $table->string('slug', 255)
                ->index()
                ->unique()
                ->comment('Slug para el URL');
            $table->string('excerpt', 1023)
                ->nullable()
                ->comment('Descripción breve del contenido');


            $table->boolean('is_copyright_valid')
                ->default(null)
                ->comment('Indica si se ha comprobado que el contenido no contiene copyright. Si es null, no se ha comprobado');
            $table->timestamp('processed_at')
                ->nullable()
                ->comment('Fecha de procesado del contenido, verificaciones, etc Por ejemplo para revisar autoría del contenido o revisar si hay actualizaciones en la nube.');
            $table->timestamp('published_at')
                ->nullable()
                ->comment('Fecha de publicación del contenido');
            $table->timestamp('programated_at')
                ->nullable()
                ->comment('Fecha en la que está programada la programación del contenido, deberá ser previamente visible. Si es null, no está programada y estará visible en cualquier momento');
            $table->boolean('is_visible')
                ->default(false)
                ->comment('Indica si el contenido está visible');

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
            $table->dropForeign(['author_id']);
            $table->dropForeign(['platform_id']);
            $table->dropForeign(['status_id']);
            $table->dropForeign(['type_id']);
            $table->dropForeign(['image_id']);
        });
    }
}
