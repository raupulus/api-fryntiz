<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateContentRelatedTable
 */
class CreateContentRelatedTable extends Migration
{
    private $tableName = 'content_related';
    private $tableComment = 'Contenido relacionado a otro contenido.';

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
                ->comment('FK al contenido desde el que se asocia');
            $table->foreign('content_id')
                ->references('id')->on('contents')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->bigInteger('content_related_id')
                ->index()
                ->nullable()
                ->comment('FK al contenido asociado');
            $table->foreign('content_related_id')
                ->references('id')->on('contents')
                ->onUpdate('cascade')
                ->onDelete('cascade');
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
            $table->dropForeign(['content_related_id']);
        });
    }
}
