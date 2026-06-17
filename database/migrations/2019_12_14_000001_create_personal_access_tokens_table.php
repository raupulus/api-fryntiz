<?php

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
            $table->comment('Almacena los registros correspondientes a personal access tokens para su integración y uso general en el sistema.');
            $table->id()->comment('Identificador único autoincremental de este registro en la base de datos.');
            $table->morphs('tokenable');
            $table->text('name')->comment('Nombre');
            $table->string('token', 64)->unique()->comment('Hash o cadena criptográfica del token');
            $table->text('abilities')->nullable()->comment('Campo que almacena el abilities específico para este registro según la lógica de negocio.');
            $table->timestamp('last_used_at')->nullable()->comment('Campo que almacena el last used at específico para este registro según la lógica de negocio.');
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
