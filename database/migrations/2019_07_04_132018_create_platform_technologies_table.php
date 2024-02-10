<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreatePlatformTagsTable
 *
 * Tabla para asociar las etiquetas a las plataformas.
 *
 */
class CreatePlatformTechnologiesTable extends Migration
{
    private $tableName = 'platform_technologies';
    private $tableComment = 'Asociación de tecnologías con plataformas';

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
            $table->unsignedBigInteger('technology_id')
                ->comment('Relación con la tecnología');
            $table->foreign('technology_id')
                ->references('id')->on('technologies')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->timestamps();
            $table->softDeletes();



            $table->unique(["platform_id", "technology_id"], 'platform_technologies_unique');
            $table->index(["platform_id", "technology_id"], 'platform_technologies_index');
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
            $table->dropForeign(['technology_id']);


            $table->dropUnique('platform_technologies_unique');
            $table->dropIndex('platform_technologies_index');
        });
    }
}
