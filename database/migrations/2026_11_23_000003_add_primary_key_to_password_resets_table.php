<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade clave primaria autoincremental `id` a `password_resets` (convención
     * obligatoria de esquema del proyecto y replicación lógica PostgreSQL) y
     * corrige el comentario de la columna `created_at`.
     */
    public function up(): void
    {
        if (! Schema::hasTable('password_resets')) {
            return;
        }

        if (! Schema::hasColumn('password_resets', 'id')) {
            DB::statement('ALTER TABLE password_resets ADD COLUMN id BIGSERIAL PRIMARY KEY');
            DB::statement("COMMENT ON COLUMN password_resets.id IS 'Identificador único autoincremental'");
        }

        DB::statement("COMMENT ON COLUMN password_resets.created_at IS 'Fecha y hora de creación del token de restablecimiento'");
    }

    /**
     * Revierte la clave primaria añadida en `password_resets`.
     */
    public function down(): void
    {
        if (! Schema::hasTable('password_resets') || ! Schema::hasColumn('password_resets', 'id')) {
            return;
        }

        DB::statement('ALTER TABLE password_resets DROP CONSTRAINT IF EXISTS password_resets_pkey');
        DB::statement('ALTER TABLE password_resets DROP COLUMN IF EXISTS id');
    }
};
