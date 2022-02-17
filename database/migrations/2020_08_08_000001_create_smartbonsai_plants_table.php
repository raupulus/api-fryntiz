<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateSmartbonsaiPlantsTable
 */
class CreateSmartbonsaiPlantsTable extends Migration
{
    private $tableName = 'smartplant_plants';
    private $tableComment = 'Cada tipo de planta sobre la que se tomarán registros con sensores.';

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
            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->comment('Usuario asociado');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->string('name')
                ->comment('Nombre común de la planta');
            $table->string('name_scientific')
                ->comment('Nombre científico de la planta');
            $table->string('description')
                ->comment('Descripción general de la planta');
            $table->text('details')
                ->comment('Descripción avanzada con detalles de la planta');
            $table->text('image')
                ->nullable()
                ->default('smartplant/default.jpg')
                ->comment('Imagen que representa a la planta');
            $table->timestamp('start_at')
                ->comment('Momento en el que se ha sembrado');
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
            $table->dropForeign(['user_id']);
        });
    }
}
