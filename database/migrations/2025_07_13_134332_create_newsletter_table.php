
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
            $table->comment('Tabla para almacenar información de newsletter');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->id()->comment('Identificador único');

            // Relación con plataforma
            $table->unsignedBigInteger('platform_id');
            $table->foreign('platform_id')
                ->references('id')->on('platforms')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            // Información del suscriptor
            $table->string('email', 255)->comment('Correo electrónico');
            $table->string('name', 100)->nullable()->comment('Nombre'); // Nombre del suscriptor

            // Verificación de email
            $table->boolean('is_verified')->default(false)->comment('Indicador de tipo booleano para is verified');
            $table->string('verification_token', 60)->nullable()->unique()->comment('Columna verification token');
            $table->timestamp('verified_at')->nullable()->comment('Columna verified at');

            // Token para desuscripción
            $table->string('unsubscribe_token', 60)->unique()->comment('Columna unsubscribe token');

            // Estado de suscripción
            $table->enum('status', ['active', 'inactive', 'unsubscribed', 'bounced'])->default('active')->comment('Estado actual');
            $table->timestamp('unsubscribed_at')->nullable()->comment('Columna unsubscribed at');

            // Información adicional
            $table->string('subscription_source', 50)->nullable()->comment('Columna subscription source'); // web, api, import, etc.
            $table->string('language', 5)->default('es')->comment('Columna language'); // Idioma preferido
            $table->json('preferences')->nullable()->comment('Columna preferences'); // Preferencias de contenido

            // Metadatos
            $table->ipAddress('ip_address')->nullable()->comment('Columna ip address'); // IP de suscripción
            $table->string('user_agent', 500)->nullable()->comment('Navegador o agente de usuario'); // User agent
            $table->json('metadata')->nullable()->comment('Columna metadata'); // Datos adicionales flexibles

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
