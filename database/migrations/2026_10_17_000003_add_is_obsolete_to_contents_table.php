<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contenidos obsoletos: desactualizados, o de un programa que ya no existe,
 * pero que siguen publicados porque le sirven a alguien.
 *
 * Es una marca aparte del estado: un contenido publicado no cambia de estado
 * (D31). En los listados de la API van al final, sin pisar el orden que se
 * pida (D52).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->boolean('is_obsolete')->default(false)
                ->comment('Indica si el contenido está obsoleto: sigue publicado, pero va al final de los listados');
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropColumn('is_obsolete');
        });
    }
};
