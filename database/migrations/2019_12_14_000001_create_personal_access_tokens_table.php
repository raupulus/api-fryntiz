<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->comment('Tokens de la API: sesiones humanas y dispositivos IoT, acotados por abilities.');
            $table->id()->comment('Identificador único autoincremental de este registro en la base de datos.');
            // `morphs()` no deja comentar las dos columnas que genera, y aquí
            // conviene decir qué son: el token puede colgar de un usuario o,
            // más adelante, de otra entidad.
            $table->string('tokenable_type')
                ->comment('Clase del modelo dueño del token. Hoy siempre App\\Models\\User.');
            $table->unsignedBigInteger('tokenable_id')
                ->comment('Id del dueño del token dentro de esa clase.');
            $table->index(['tokenable_type', 'tokenable_id']);
            $table->text('name')->comment('Nombre con el que se identifica el token (el contexto donde se usa).');
            $table->string('token', 64)->unique()->comment('Hash o cadena criptográfica del token');
            $table->text('abilities')->nullable()->comment('Permisos del token. Ver App\\Support\\Auth\\TokenAbilities: el comodín * no se emite nunca.');
            $table->timestamp('last_used_at')->nullable()->comment('Última vez que se autenticó con este token. Null si no se ha usado nunca.');
            $table->timestamp('expires_at')->nullable()->index()->comment('Marca de tiempo que indica cuándo expira la validez del registro o token.');
            $table->timestamps()->comment('Marcas de tiempo utilizadas por Eloquent para llevar el registro de creación y última actualización.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
