<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajustes de `emails` para el formulario de contacto (C4).
 *
 *  - `platform_id`: el mensaje llega desde una web concreta y hay que saber
 *    cuál para poder filtrar en el panel y para resolver el buzón de destino.
 *    Hasta ahora sólo quedaba `app_domain`, un texto suelto.
 *  - `captcha_score` era `decimal(3,1)`. reCAPTCHA v3 devuelve de 0.0 a 1.0 con
 *    dos decimales; con un decimal, 0.34 y 0.35 se guardan igual y el corte de
 *    0.5 deja de distinguir nada útil.
 *  - Índices para las dos consultas que hace el anti-duplicado en cada envío.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            if (! Schema::hasColumn('emails', 'platform_id')) {
                $table->foreignId('platform_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('platforms')
                    ->nullOnDelete()
                    ->comment('Plataforma desde la que se envió el mensaje.');
            }

            $table->decimal('captcha_score', 4, 2)
                ->nullable()
                ->change();

            // El anti-duplicado consulta por email y por (ip, created_at) en
            // cada envío: sin índice son dos escaneos completos por mensaje.
            $table->index(['email', 'created_at'], 'emails_email_created_at_index');
            $table->index(['client_ip', 'created_at'], 'emails_client_ip_created_at_index');
            $table->index(['send', 'sent_at'], 'emails_send_sent_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropIndex('emails_email_created_at_index');
            $table->dropIndex('emails_client_ip_created_at_index');
            $table->dropIndex('emails_send_sent_at_index');

            if (Schema::hasColumn('emails', 'platform_id')) {
                $table->dropConstrainedForeignId('platform_id');
            }

            $table->decimal('captcha_score', 3, 1)->nullable()->change();
        });
    }
};
