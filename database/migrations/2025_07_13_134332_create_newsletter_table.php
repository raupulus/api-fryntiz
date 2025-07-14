
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
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->id();

            // Relación con plataforma
            $table->unsignedBigInteger('platform_id');
            $table->foreign('platform_id')
                ->references('id')->on('platforms')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            // Información del suscriptor
            $table->string('email', 255);
            $table->string('name', 100)->nullable(); // Nombre del suscriptor

            // Verificación de email
            $table->boolean('is_verified')->default(false);
            $table->string('verification_token', 60)->nullable()->unique();
            $table->timestamp('verified_at')->nullable();

            // Token para desuscripción
            $table->string('unsubscribe_token', 60)->unique();

            // Estado de suscripción
            $table->enum('status', ['active', 'inactive', 'unsubscribed', 'bounced'])->default('active');
            $table->timestamp('unsubscribed_at')->nullable();

            // Información adicional
            $table->string('subscription_source', 50)->nullable(); // web, api, import, etc.
            $table->string('language', 5)->default('es'); // Idioma preferido
            $table->json('preferences')->nullable(); // Preferencias de contenido

            // Metadatos
            $table->ipAddress('ip_address')->nullable(); // IP de suscripción
            $table->string('user_agent', 500)->nullable(); // User agent
            $table->json('metadata')->nullable(); // Datos adicionales flexibles

            $table->timestamps();

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
