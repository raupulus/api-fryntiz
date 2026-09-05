<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreatePlatformCategoriesTable
 *
 * Tabla para asociar las categorías a las plataformas.
 */
class CreatePlatformCategoriesTable extends Migration
{
    private $tableName = 'platform_categories';

    private $tableComment = 'Asociación de categorías con plataformas';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->comment('Asociación de categorías con plataformas');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único');
            $table->unsignedBigInteger('platform_id')
                ->comment('Relación con la plataforma');
            $table->foreign('platform_id')
                ->references('id')->on('platforms')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->unsignedBigInteger('category_id')
                ->comment('Relación con la categoría');
            $table->foreign('category_id')
                ->references('id')->on('categories')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->timestamps()->comment('Marcas de tiempo de creación y actualización');

            $table->unique(['platform_id', 'category_id'], 'platform_category_unique');
            $table->index(['platform_id', 'category_id'], 'platform_category_index');
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
            $table->dropForeign(['platform_id']);
            $table->dropForeign(['category_id']);

            $table->dropUnique('platform_category_unique');
            $table->dropIndex('platform_category_index');
        });
    }
}
