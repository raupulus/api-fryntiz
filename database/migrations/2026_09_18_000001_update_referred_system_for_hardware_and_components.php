<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reestructura el sistema de referidos y afiliados:
 * - Elimina la relación 1:1 obsoleta en `hardware_devices.referred_thing_id`.
 * - Reorienta `referred_things` como tabla de enlaces de compra asociados a
 *   `hardware_devices` (dispositivo completo) y opcionalmente a `hardware_components`
 *   (componentes específicos de ese hardware).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hardware_devices', function (Blueprint $table): void {
            $table->dropForeign(['referred_thing_id']);
            $table->dropColumn('referred_thing_id');
        });

        Schema::table('referred_things', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->dropUnique(['name']);
        });

        Schema::table('referred_things', function (Blueprint $table): void {
            $table->string('name', 255)->nullable()->change()
                ->comment('Título o nota opcional del enlace (ej: Pack con disipador)');
            $table->text('url')->change()
                ->comment('URL de compra afiliada con parámetros de tracking');

            $table->unsignedBigInteger('hardware_device_id')
                ->comment('Dispositivo hardware al que pertenece este enlace de compra');
            $table->foreign('hardware_device_id')
                ->references('id')->on('hardware_devices')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->index('hardware_device_id');

            $table->unsignedBigInteger('hardware_component_id')
                ->nullable()
                ->comment('Componente específico al que aplica el enlace; si es nulo, aplica al dispositivo completo');
            $table->foreign('hardware_component_id')
                ->references('id')->on('hardware_components')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->index('hardware_component_id');

            $table->decimal('price', 10, 2)->nullable()
                ->comment('Precio orientativo del producto');
            $table->string('currency', 3)->default('EUR')
                ->comment('Código de moneda ISO (por defecto EUR)');
            $table->boolean('is_active')->default(true)
                ->comment('Indica si el enlace de compra está activo');
            $table->index('is_active');
        });

        DB::statement("COMMENT ON TABLE referred_things IS 'Enlaces de compra de afiliados asociados a dispositivos hardware y componentes.'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referred_things', function (Blueprint $table): void {
            $table->dropForeign(['hardware_component_id']);
            $table->dropIndex(['hardware_component_id']);
            $table->dropColumn('hardware_component_id');

            $table->dropForeign(['hardware_device_id']);
            $table->dropIndex(['hardware_device_id']);
            $table->dropColumn('hardware_device_id');

            $table->dropIndex(['is_active']);
            $table->dropColumn('is_active');
            $table->dropColumn('currency');
            $table->dropColumn('price');

            $table->string('url', 255)->nullable()->change();
            $table->string('name', 255)->unique()->change();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
        });

        Schema::table('hardware_devices', function (Blueprint $table): void {
            $table->unsignedBigInteger('referred_thing_id')
                ->nullable()
                ->comment('Relación con el dispositivo afiliado');
            $table->foreign('referred_thing_id')
                ->references('id')->on('referred_things')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');
        });

        DB::statement("COMMENT ON TABLE referred_things IS 'Productos referidos de los programas de afiliación.'");
    }
};
