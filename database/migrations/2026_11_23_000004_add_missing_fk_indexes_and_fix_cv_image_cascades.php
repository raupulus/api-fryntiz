<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Claves foráneas sin índice B-tree en PostgreSQL sobre tablas de consulta,
     * JOIN o comprobación referencial frecuente.
     *
     * @var list<array{0: string, 1: string}>
     */
    private const FK_INDEXES = [
        ['airflight_airplanes', 'hardware_device_id'],
        ['airflight_airplanes', 'user_id'],
        ['airflight_routes', 'hardware_device_id'],
        ['airflight_routes', 'user_id'],
        ['categories', 'parent_id'],
        ['content_contributors', 'user_id'],
        ['content_files', 'content_id'],
        ['content_files', 'file_id'],
        ['content_page_drafts', 'content_page_id'],
        ['content_page_raw', 'available_page_raw_id'],
        ['content_page_versions', 'user_id'],
        ['content_pages', 'current_page_raw_id'],
        ['content_pages', 'image_id'],
        ['contents', 'image_id'],
        ['cv', 'user_id'],
        ['cv', 'image_id'],
        ['cv_academic_complementary', 'curriculum_id'],
        ['cv_academic_complementary_online', 'curriculum_id'],
        ['cv_academic_training', 'curriculum_id'],
        ['cv_experience_accredited', 'curriculum_id'],
        ['cv_experience_additional', 'curriculum_id'],
        ['cv_experience_no_accredited', 'curriculum_id'],
        ['cv_experience_others', 'curriculum_id'],
        ['cv_experience_self_employed', 'curriculum_id'],
        ['cv_project_categories', 'category_id'],
        ['cv_project_categories', 'project_id'],
        ['cv_project_tags', 'project_id'],
        ['cv_project_tags', 'tag_id'],
        ['cv_repositories', 'repository_type_id'],
        ['emails', 'platform_id'],
        ['emails', 'user_id'],
        ['file_thumbnails', 'file_id'],
        ['file_thumbnails', 'file_type_id'],
        ['files', 'file_type_id'],
        ['files', 'user_id'],
        ['galleries', 'user_id'],
        ['gallery_images', 'image_id'],
        ['hardware_components', 'hardware_available_component_id'],
        ['hardware_components', 'hardware_device_id'],
        ['hardware_devices', 'hardware_type_id'],
        ['hardware_devices', 'image_id'],
        ['hardware_devices', 'user_id'],
        ['hardware_energy', 'energy_source_type_id'],
        ['hardware_energy', 'hardware_device_monitorized_id'],
        ['keycounter_keyboard', 'hardware_device_id'],
        ['keycounter_mouse', 'hardware_device_id'],
        ['platform_categories', 'category_id'],
        ['platform_tags', 'tag_id'],
        ['platforms', 'user_id'],
        ['printer_stack', 'user_id'],
        ['referred_things', 'referred_platform_id'],
        ['referred_users', 'referred_platform_id'],
        ['referred_users', 'user_id'],
        ['smartplant_plants', 'user_id'],
        ['smartplant_registers', 'hardware_device_id'],
        ['user_data', 'user_id'],
        ['user_details', 'user_id'],
        ['user_settings', 'user_id'],
        ['user_social', 'social_network_id'],
        ['user_social', 'user_id'],
        ['users', 'role_id'],
    ];

    /**
     * Las 13 tablas del módulo CV cuya FK `image_id -> files.id` tenía
     * `ON DELETE CASCADE` (borrando filas enteras del CV al eliminar una imagen)
     * en lugar de `ON DELETE SET NULL`.
     *
     * @var list<string>
     */
    private const CV_IMAGE_TABLES = [
        'cv_academic_complementary',
        'cv_academic_complementary_online',
        'cv_academic_training',
        'cv_collaborations',
        'cv_experience_accredited',
        'cv_experience_additional',
        'cv_experience_no_accredited',
        'cv_experience_others',
        'cv_experience_self_employed',
        'cv_hobbies',
        'cv_jobs',
        'cv_projects',
        'cv_services',
    ];

    public function up(): void
    {
        foreach (self::FK_INDEXES as [$table, $column]) {
            $indexName = "{$table}_{$column}_idx";
            DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON {$table} ({$column})");
        }

        foreach (self::CV_IMAGE_TABLES as $table) {
            $constraint = "{$table}_image_id_foreign";
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraint}");

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->foreign('image_id')
                    ->references('id')
                    ->on('files')
                    ->onUpdate('CASCADE')
                    ->onDelete('SET NULL');
            });

            DB::statement("CREATE INDEX IF NOT EXISTS {$table}_image_id_idx ON {$table} (image_id)");
        }
    }

    public function down(): void
    {
        foreach (self::CV_IMAGE_TABLES as $table) {
            DB::statement("DROP INDEX IF EXISTS {$table}_image_id_idx");
            $constraint = "{$table}_image_id_foreign";
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraint}");

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->foreign('image_id')
                    ->references('id')
                    ->on('files')
                    ->onUpdate('CASCADE')
                    ->onDelete('CASCADE');
            });
        }

        foreach (self::FK_INDEXES as [$table, $column]) {
            $indexName = "{$table}_{$column}_idx";
            DB::statement("DROP INDEX IF EXISTS {$indexName}");
        }
    }
};
