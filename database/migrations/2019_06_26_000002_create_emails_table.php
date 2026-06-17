<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateEmailsTable
 */
class CreateEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->comment('Almacena los registros correspondientes a emails para su integración y uso general en el sistema.');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único autoincremental de este registro en la base de datos.');
            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->comment('Usuario al que se le envía el mensaje');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade')->comment('Clave foránea que relaciona este registro con el user al que pertenece.');
            $table->unsignedBigInteger('language_id')
                ->default(1)
                ->nullable()
                ->comment('Idioma del usuario que envía el mensaje para que se le responda en su idioma');
            $table->foreign('language_id')
                ->references('id')->on('languages')
                ->onUpdate('cascade')
                ->onDelete('cascade')->comment('Clave foránea que relaciona este registro con el language al que pertenece.');
            $table->string('email', 511)->comment('Correo electrónico');
            $table->string('subject', 511)->comment('Asunto del mensaje o correo electrónico');
            $table->text('message')->comment('Campo que almacena el message específico para este registro según la lógica de negocio.');
            $table->boolean('privacity')
                ->default(0)
                ->comment('Indica si acepta políticas de privacidad desde el apartado que envía el mensaje de contacto.');
            $table->boolean('contactme')
                ->default(0)
                ->comment('Indica si permite ser contactado.');
            $table->decimal('captcha_score', 3, 1)
                ->nullable()
                ->comment('Puntuación asignada por un validador de captcha (del 1.0 al 10.0)');
            $table->string('server_ip', 255)
                ->nullable()
                ->comment('Ip del servidor desde el que se ha enviado.');
            $table->string('client_ip', 255)
                ->nullable()
                ->comment('Ip que se ha obtenido del cliente.');
            $table->string('client_user_agent', 255)
                ->nullable()
                ->comment('Navegador que se ha obtenido del navegador cliente.');
            $table->string('client_referer', 255)
                ->nullable()
                ->comment('Página desde la que se ha obtenido que viene el navegador cliente.');
            $table->json('client_accept_language')
                ->nullable()
                ->comment('Listado de idiomas que acepta el navegador cliente.');
            $table->string('app_name', 511)
                ->nullable()
                ->comment('Nombre de la aplicación desde la que se envía el mensaje.');
            $table->string('app_domain', 255)
                ->nullable()
                ->comment('Dominio de la aplicación desde la que se envía el mensaje.');
            $table->json('attributes')
                ->nullable()
                ->comment('Otros datos de interés dentro de un json, por ejemplo: {phone: XXX-XXX-XXX, age: 29}');
            $table->smallInteger('priority')
                ->default(0)
                ->comment('Prioridad del mensaje, cuanto más alto más prioridad. Menor a 3 no se envía.');
            $table->boolean('send')
                ->default(0)
                ->comment('Indica si el mensaje se debe enviar o no.');
            $table->smallInteger('attempts')
                ->default(0)
                ->comment('Número de intentos de envío de este mensaje.');
            $table->timestamp('sent_at')
                ->nullable()
                ->comment('Fecha y hora en la que se envió el mensaje.');
            $table->smallInteger('error_code')
                ->nullable()
                ->comment('Código de error en caso de que el mensaje no se haya enviado correctamente.');
            $table->timestamp('error_at')
                ->nullable()
                ->comment('Fecha y hora en la que se produjo el error.');
            $table->text('error_message')
                ->nullable()
                ->comment('Mensaje de error en caso de que el mensaje no se haya enviado correctamente.');

            $table->timestamps()->comment('Marcas de tiempo de creación y actualización');
            $table->softDeletes()->comment('Marca de tiempo para borrado lógico');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('emails', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['language_id']);
        });
    }
}
