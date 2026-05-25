<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('websockets_statistics_entries');
    }

    public function down(): void
    {
        // No se recrea la tabla — paquete eliminado
    }
};
