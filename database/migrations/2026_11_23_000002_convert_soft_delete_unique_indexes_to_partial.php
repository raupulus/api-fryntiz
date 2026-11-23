<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Índices únicos en tablas con SoftDeletes que deben ser parciales
     * (`WHERE deleted_at IS NULL`) para permitir recrear registros borrados.
     *
     * @var list<array{table: string, name: string, columns: string}>
     */
    private const INDEXES = [
        ['table' => 'users', 'name' => 'users_email_unique', 'columns' => 'email'],
        ['table' => 'users', 'name' => 'users_nickname_unique', 'columns' => 'nickname'],
        ['table' => 'platforms', 'name' => 'platforms_slug_unique', 'columns' => 'slug'],
        ['table' => 'contents', 'name' => 'contents_platform_id_slug_unique', 'columns' => 'platform_id, slug'],
        ['table' => 'content_pages', 'name' => 'content_pages_content_id_slug_unique', 'columns' => 'content_id, slug'],
        ['table' => 'content_categories', 'name' => 'content_categories_content_id_platform_category_id_unique', 'columns' => 'content_id, platform_category_id'],
        ['table' => 'content_tags', 'name' => 'content_tags_content_id_platform_tag_id_unique', 'columns' => 'content_id, platform_tag_id'],
        ['table' => 'content_technologies', 'name' => 'contents_technologies_unique', 'columns' => 'content_id, technology_id'],
        ['table' => 'content_contributors', 'name' => 'content_contributors_content_id_user_id_unique', 'columns' => 'content_id, user_id'],
        ['table' => 'cv', 'name' => 'cv_slug_unique', 'columns' => 'slug'],
        ['table' => 'cv', 'name' => 'cv_share_token_unique', 'columns' => 'share_token'],
        ['table' => 'hardware_devices', 'name' => 'hardware_devices_slug_unique', 'columns' => 'slug'],
        ['table' => 'hardware_energy', 'name' => 'hardware_energy_device_monitorized_role_position_unique', 'columns' => 'hardware_device_id, hardware_device_monitorized_id, role, sensor_position'],
        ['table' => 'referred_platforms', 'name' => 'referred_platforms_name_unique', 'columns' => 'name'],
        ['table' => 'referred_platforms', 'name' => 'referred_platforms_slug_unique', 'columns' => 'slug'],
        ['table' => 'referred_users', 'name' => 'referred_users_id_affiliated_unique', 'columns' => 'id_affiliated'],
        ['table' => 'referred_users', 'name' => 'referred_users_token_unique', 'columns' => 'token'],
    ];

    /**
     * Sustituye los 17 índices UNIQUE completos en tablas con SoftDeletes por
     * índices únicos parciales `WHERE deleted_at IS NULL`.
     */
    public function up(): void
    {
        foreach (self::INDEXES as $index) {
            DB::statement("ALTER TABLE {$index['table']} DROP CONSTRAINT IF EXISTS {$index['name']}");
            DB::statement("DROP INDEX IF EXISTS {$index['name']}");
            DB::statement("CREATE UNIQUE INDEX {$index['name']} ON {$index['table']} ({$index['columns']}) WHERE deleted_at IS NULL");
        }
    }

    /**
     * Restaura los índices UNIQUE completos originales.
     */
    public function down(): void
    {
        foreach (self::INDEXES as $index) {
            DB::statement("DROP INDEX IF EXISTS {$index['name']}");
            DB::statement("CREATE UNIQUE INDEX {$index['name']} ON {$index['table']} ({$index['columns']})");
        }
    }
};
