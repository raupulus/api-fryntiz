<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateFileTypesTable
 */
class CreateFileTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('file_types', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->comment('Usuario asociado');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->string('type', 127)
                ->comment('Tipo de archivo');
            $table->string('mime', 127)
                ->index()
                ->comment('Tipo mime que representa el tipo de archivo');
            $table->string('extension', 12)
                ->comment('Extensión con la que se representa de forma mayoritaria.');
            $table->text('icon16')->nullable();
            $table->text('icon32')->nullable();
            $table->text('icon64')->nullable();
            $table->text('icon128')->nullable();
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
        Schema::dropIfExists('file_types');
    }
}
