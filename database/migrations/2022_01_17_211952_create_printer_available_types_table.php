<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreatePrinterTypesTable
 */
class CreatePrinterAvailableTypesTable extends Migration
{
    private $tableName = 'printer_available_types';
    private $tableComment = 'Tipos de impresoras';

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
            $table->string('name', 255)
                ->comment('Nombre del tipo de impresora');
            $table->string('slug', 255)
                ->comment('Nombre del tipo de impresora');
            $table->text('description')
                ->nullable()
                ->comment('Descripción');
            $table->timestamps();
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
        Schema::dropIfExists($this->tableName);
    }
}
