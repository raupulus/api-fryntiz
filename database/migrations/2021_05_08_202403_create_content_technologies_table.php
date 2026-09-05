<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateContentTagsTable
 */
class CreateContentTechnologiesTable extends Migration
{
    private $tableName = 'content_technologies';

    private $tableComment = 'Tecnologías de la plataforma asociadas al contenido';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->comment('Tecnologías de la plataforma asociadas al contenido');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único');
            $table->bigInteger('content_id')
                ->index()
                ->nullable()
                ->comment('FK al contenido asociado');
            $table->foreign('content_id')
                ->references('id')->on('contents')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->bigInteger('technology_id')
                ->index()
                ->nullable()
                ->comment('FK a la tecnología asociada');
            $table->foreign('technology_id')
                ->references('id')->on('technologies')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->timestamps()->comment('Marcas de tiempo de creación y actualización');
            $table->softDeletes()->comment('Marca de tiempo para borrado lógico');

            $table->unique(['content_id', 'technology_id'], 'contents_technologies_unique');
            // $table->index(['content_id', 'platform_technology_id']);
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
            $table->dropForeign(['contents_technologies_unique']);
        });
    }
}
