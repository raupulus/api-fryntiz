<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colaborador automático por plataforma (B3 y DUDA-1 del plan de contenidos
 * del 2026-09-24).
 *
 * Un Editor edita los contenidos donde es autor o colaborador. Con esta marca en
 * una de sus plataformas, entra como colaborador en todos los contenidos de esa
 * plataforma: los que ya existen al activarla y los que se creen después. Si
 * alguien le quita de un contenido a mano, esa baja se respeta y no vuelve a
 * entrar. Al desactivarla deja de entrar en los nuevos y sigue en los que ya
 * estaba.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_user', function (Blueprint $table) {
            $table->boolean('auto_contributor')
                ->default(false)
                ->comment('Si el editor entra como colaborador en todos los contenidos de la plataforma, los que ya existen al activarlo y los nuevos. Una baja manual en un contenido se respeta.');
        });
    }

    public function down(): void
    {
        Schema::table('platform_user', function (Blueprint $table) {
            $table->dropColumn('auto_contributor');
        });
    }
};
