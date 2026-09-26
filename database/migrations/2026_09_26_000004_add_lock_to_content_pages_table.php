<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloqueo de cada página a un usuario mientras la edita (P4 de la auditoría de
 * contenidos del 2026-09-24).
 *
 * Quien abre la página para editarla la bloquea; los demás, y sus otras
 * pestañas, la ven en lectura. Se renueva con cada autoguardado y caduca a los
 * 2 minutos sin renovar. Cambiar el bloqueo no toca `updated_at`: esa fecha es
 * la que dice si la página se ha guardado mientras se editaba.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_pages', function (Blueprint $table) {
            $table->unsignedBigInteger('locked_by_user_id')->nullable()
                ->comment('Usuario que tiene la página bloqueada para editarla; vacío si nadie');
            $table->foreign('locked_by_user_id')->references('id')->on('users')
                ->onUpdate('CASCADE')->onDelete('SET NULL');

            $table->timestamp('locked_since')->nullable()
                ->comment('Desde cuándo (UTC) la tiene bloqueada ese usuario, para el aviso «la está editando desde hace…»');
            $table->timestamp('locked_at')->nullable()
                ->comment('Última renovación del bloqueo (UTC): caduca a los 2 minutos sin renovar');
            $table->string('lock_token', 64)->nullable()
                ->comment('Pestaña que tiene el bloqueo: otra pestaña del mismo usuario la ve en lectura');

            $table->index('locked_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('content_pages', function (Blueprint $table) {
            $table->dropForeign(['locked_by_user_id']);
            $table->dropIndex(['locked_by_user_id']);
            $table->dropColumn(['locked_by_user_id', 'locked_since', 'locked_at', 'lock_token']);
        });
    }
};
