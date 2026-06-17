<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade onDelete cascade a la FK content_id de content_daily_views.
 * Al eliminar un contenido permanentemente (forceDelete), se eliminan sus registros de vistas.
 * El soft delete NO dispara el cascade (el registro sigue existiendo en contents).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_daily_views', function (Blueprint $table) {
            $table->dropForeign(['content_id']);

            $table->foreign('content_id')
                ->references('id')
                ->on('contents')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('content_daily_views', function (Blueprint $table) {
            $table->dropForeign(['content_id']);

            $table->foreign('content_id')
                ->references('id')
                ->on('contents');
        });
    }
};
