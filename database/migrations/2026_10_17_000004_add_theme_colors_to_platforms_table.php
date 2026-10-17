<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los colores del tema de cada plataforma, para generar imágenes que casen con
 * su web. Cada web es un frontend aparte que consume la API, así que la API no
 * sabe de qué color es: se apuntan a mano una vez. No salen por la API.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platforms', function (Blueprint $table) {
            $table->jsonb('theme_colors')->nullable()
                ->comment('Hasta 5 colores del tema de su web ([{hex, role}]), para generar imágenes con IA que casen con ella');
        });
    }

    public function down(): void
    {
        Schema::table('platforms', function (Blueprint $table) {
            $table->dropColumn('theme_colors');
        });
    }
};
