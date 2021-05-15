<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateContentFilesTable
 */
class CreateContentFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('content_files', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->bigInteger('file_id')
                ->nullable()
                ->comment('FK al al archivo');
            $table->foreign('file_id')
                ->references('id')->on('files')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->bigInteger('content_id')
                ->nullable()
                ->comment('FK al contenido que se asocia con el archivo');
            $table->foreign('content_id')
                ->references('id')->on('contents')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('content_files', function (Blueprint $table) {
            $table->dropForeign(['file_id']);
            $table->dropForeign(['content_id']);
        });
    }
}
