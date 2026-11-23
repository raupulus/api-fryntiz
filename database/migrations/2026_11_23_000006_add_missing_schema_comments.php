<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Completa los comentarios faltantes en tablas y columnas PK/FK recientes
     * detectados en la auditoría de esquema PostgreSQL.
     */
    public function up(): void
    {
        DB::statement("COMMENT ON TABLE ai_providers IS 'Proveedores externos de IA configurados para el asistente editorial'");
        DB::statement("COMMENT ON TABLE platform_editor_profiles IS 'Perfiles editoriales por plataforma usados por el asistente de IA'");

        DB::statement("COMMENT ON COLUMN ai_providers.id IS 'Identificador único del proveedor de IA'");
        DB::statement("COMMENT ON COLUMN energy_source_types.id IS 'Identificador único del tipo de fuente energética'");
        DB::statement("COMMENT ON COLUMN platform_editor_profiles.id IS 'Identificador único del perfil editorial'");
        DB::statement("COMMENT ON COLUMN platform_user.id IS 'Identificador único de la asignación editor-plataforma'");
        DB::statement("COMMENT ON COLUMN printers.hardware_device_id IS 'Dispositivo hardware anfitrión al que está conectada la impresora'");
        DB::statement("COMMENT ON COLUMN printer_stack.user_id IS 'Usuario que encoló el trabajo de impresión'");
    }

    public function down(): void
    {
        DB::statement('COMMENT ON TABLE ai_providers IS NULL');
        DB::statement('COMMENT ON TABLE platform_editor_profiles IS NULL');
        DB::statement('COMMENT ON COLUMN ai_providers.id IS NULL');
        DB::statement('COMMENT ON COLUMN energy_source_types.id IS NULL');
        DB::statement('COMMENT ON COLUMN platform_editor_profiles.id IS NULL');
        DB::statement('COMMENT ON COLUMN platform_user.id IS NULL');
        DB::statement('COMMENT ON COLUMN printers.hardware_device_id IS NULL');
        DB::statement('COMMENT ON COLUMN printer_stack.user_id IS NULL');
    }
};
