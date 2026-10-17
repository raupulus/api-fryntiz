<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El modelo con el que cada proveedor genera y mejora imágenes (las portadas
 * de las entradas y de sus páginas). Es otro que el de texto; en Mistral, el
 * que maneja su herramienta de imágenes. Vacío, el proveedor no hace imágenes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_providers', function (Blueprint $table) {
            $table->string('image_model', 255)->nullable()
                ->comment('Modelo de imágenes en la API del proveedor (p. ej. gemini-nano-banana-2.1); vacío, no genera imágenes');
        });
    }

    public function down(): void
    {
        Schema::table('ai_providers', function (Blueprint $table) {
            $table->dropColumn('image_model');
        });
    }
};
