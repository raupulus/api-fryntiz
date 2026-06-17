
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsletterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('newsletter', function (Blueprint $table) {
            $table->comment('Almacena los registros correspondientes a newsletter para su integración y uso general en el sistema.');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->id()->comment('Identificador único autoincremental de este registro en la base de datos.');

            // Relación con plataforma
            $table->unsignedBigInteger('platform_id')->comment('Clave foránea que relaciona este registro con el platform al que pertenece.');
            $table->foreign('platform_id')
                ->references('id')->on('platforms')
                ->onUpdate('cascade')
                ->onDelete('cascade')->comment('Clave foránea que relaciona este registro con el platform al que pertenece.');

            // Información del suscriptor
            $table->string('email', 255)->comment('Correo electrónico');
            $table->string('name', 100)->nullable()->comment('Nombre'); // Nombre del suscriptor

            // Verificación de email
            $table->boolean('is_verified')->default(false)->comment('Indicador de tipo booleano para is verified');
            $table->string('verification_token', 60)->nullable()->unique()->comment('Token criptográfico único generado para la verificación de identidad o correo');
            $table->timestamp('verified_at')->nullable()->comment('Campo que almacena el verified at específico para este registro según la lógica de negocio.');

            // Token para desuscripción
            $table->string('unsubscribe_token', 60)->unique()->comment('Columna unsubscribe token');

            // Estado de suscripción
            $table->enum('status', ['active', 'inactive', 'unsubscribed', 'bounced'])->default('active')->comment('Estado actual');
            $table->timestamp('unsubscribed_at')->nullable()->comment('Campo que almacena el unsubscribed at específico para este registro según la lógica de negocio.');

            // Información adicional
            $table->string('subscription_source', 50)->nullable()->comment('Columna subscription source'); // web, api, import, etc.
            $table->string('language', 5)->default('es')->comment('Columna language'); // Idioma preferido
            $table->json('preferences')->nullable()->comment('Campo que almacena el preferences específico para este registro según la lógica de negocio.'); // Preferencias de contenido

            // Metadatos
            $table->ipAddress('ip_address')->nullable()->comment('Campo que almacena el ip address específico para este registro según la lógica de negocio.'); // IP de suscripción
            $table->string('user_agent', 500)->nullable()->comment('Navegador o agente de usuario'); // User agent
            $table->json('metadata')->nullable()->comment('Campo que almacena el metadata específico para este registro según la lógica de negocio.'); // Datos adicionales flexibles

            $table->timestamps()->comment('Marcas de tiempo de creación y actualización');

            // Índices para optimización
            $table->index(['platform_id', 'email']); // Consultas por plataforma y email
            $table->index('status'); // Filtros por estado
            $table->index('is_verified'); // Filtros por verificación
            $table->unique(['platform_id', 'email']); // Evita duplicados por plataforma
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('newsletter');
    }
}
