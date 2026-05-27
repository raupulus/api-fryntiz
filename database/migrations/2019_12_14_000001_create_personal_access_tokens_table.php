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
            $table->comment('Tabla para almacenar información de personal access tokens');
            $table->id()->comment('Identificador único');
            $table->morphs('tokenable');
            $table->text('name')->comment('Nombre');
            $table->string('token', 64)->unique()->comment('Columna token');
            $table->text('abilities')->nullable()->comment('Columna abilities');
            $table->timestamp('last_used_at')->nullable()->comment('Columna last used at');
            $table->timestamp('expires_at')->nullable()->index()->comment('Columna expires at');
            $table->timestamps()->comment('Marcas de tiempo de creación y actualización');
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
