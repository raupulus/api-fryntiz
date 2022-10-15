<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreatePlatformCategoriesTable
 *
 * Tabla para asociar las categorías a las plataformas.
 *
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
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
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
            $table->timestamps();
            $table->softDeletes();



            $table->unique(["platform_id", "category_id"], 'platform_category_unique');
            $table->index(["platform_id", "category_id"], 'platform_category_index');
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
