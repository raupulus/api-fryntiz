<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lo que le dice al asistente IA quién escribe y cómo.
 *
 * Cada plataforma tiene su instrucción editorial por defecto, y además puede
 * tener perfiles de editor (guía, noticia, blog…) con la suya. Una entrada
 * con perfil usa la del perfil, que **sustituye** a la de la plataforma; sin
 * perfil, la de la plataforma. Las reglas técnicas de cada modo las añade
 * siempre el asistente (D50).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platforms', function (Blueprint $table) {
            $table->text('system_instruction')->nullable()
                ->comment('Instrucción editorial por defecto del asistente IA en esta plataforma; vacía, la genérica');
        });

        Schema::create('platform_editor_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('platform_id')
                ->comment('Plataforma a la que pertenece el perfil');
            $table->foreign('platform_id')->references('id')->on('platforms')
                ->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->string('name', 255)
                ->comment('Nombre del perfil, el que se elige en la entrada (p. ej. «Guía», «Noticia»)');
            $table->text('system_instruction')
                ->comment('Instrucción editorial del perfil; sustituye a la de la plataforma');
            $table->timestamps();

            $table->unique(['platform_id', 'name']);
        });

        Schema::table('contents', function (Blueprint $table) {
            $table->unsignedBigInteger('editor_profile_id')->nullable()
                ->comment('Perfil de editor del asistente IA; vacío, el de la plataforma');
            $table->foreign('editor_profile_id')->references('id')->on('platform_editor_profiles')
                ->onUpdate('CASCADE')->onDelete('SET NULL');
            $table->index('editor_profile_id');
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropForeign(['editor_profile_id']);
            $table->dropIndex(['editor_profile_id']);
            $table->dropColumn('editor_profile_id');
        });

        Schema::dropIfExists('platform_editor_profiles');

        Schema::table('platforms', function (Blueprint $table) {
            $table->dropColumn('system_instruction');
        });
    }
};
