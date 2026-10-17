<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los proveedores de IA del asistente del editor de páginas, con su modelo y
 * su clave. Los da de alta el superadmin en el panel (Configuración →
 * Proveedores de IA) y valen para todas las plataformas.
 *
 * La clave se guarda cifrada con la `APP_KEY` (cast `EncryptedString`). Esta
 * migración no trae ninguna fila: las claves se pegan a mano en el panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32)->unique()
                ->comment('Proveedor (AiProviderEnum): gemini, mistral, xai u openai. Uno de cada');
            $table->string('model', 255)
                ->comment('Identificador del modelo en la API del proveedor (p. ej. gemini-3.8-flash)');
            $table->text('api_key')->nullable()
                ->comment('Clave de la API cifrada con la APP_KEY; ilegible si la APP_KEY cambia');
            $table->boolean('is_active')->default(true)
                ->comment('Si sale en el selector del asistente');
            $table->boolean('is_default')->default(false)
                ->comment('El que sale elegido en el asistente; sólo uno');
            $table->boolean('sends_reasoning_level')->default(true)
                ->comment('Si se manda el nivel de razonamiento: a un modelo sin razonamiento le da error');
            $table->boolean('requests_reasoning_summary')->default(false)
                ->comment('Si se pide el resumen del razonamiento para enseñarlo mientras piensa');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};
