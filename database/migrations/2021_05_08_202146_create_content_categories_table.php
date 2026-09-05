<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateContentCategoriesTable
 */
class CreateContentCategoriesTable extends Migration
{
    private $tableName = 'content_categories';

    private $tableComment = 'Categorías asociadas a un contenido';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->comment('Categorías asociadas a un contenido');
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
            $table->bigInteger('platform_category_id')
                ->index()
                ->nullable()
                ->comment('FK a la plataforma asociada');
            $table->foreign('platform_category_id')
                ->references('id')->on('platform_categories')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->boolean('is_main')->default(false)->comment('Indicador de tipo booleano para is main');

            $table->timestamps()->comment('Marcas de tiempo de creación y actualización');
            $table->softDeletes()->comment('Marca de tiempo para borrado lógico');

            $table->unique(['content_id', 'platform_category_id']);
            // $table->index(['content_id', 'platform_category_id']);
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
            $table->dropForeign(['platform_category_id']);
        });
    }
}
