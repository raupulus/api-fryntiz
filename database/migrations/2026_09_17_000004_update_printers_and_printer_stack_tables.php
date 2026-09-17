<?php

declare(strict_types=1);

use App\Enums\PrinterStatusEnum;
use App\Enums\PrinterTypeEnum;
use App\Enums\PrintJobFormatEnum;
use App\Enums\PrintJobStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Actualiza el módulo de impresoras para desacoplarlo de tablas obsoletas
 * y habilitar la API REST V2 y WebSockets con soporte IoT.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Eliminar relaciones y columnas redundantes en printers
        Schema::table('printers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['printer_type_id']);
            $table->dropColumn(['user_id', 'printer_type_id']);
        });

        // 2. Eliminar tabla obsoleta sustituida por PrinterTypeEnum
        Schema::dropIfExists('printer_available_types');

        // 3. Ampliar printers con hardware_device_id obligatorio y campos operativos
        Schema::table('printers', function (Blueprint $table) {
            $table->unsignedBigInteger('hardware_device_id')->nullable(false)->change();
            $table->index('hardware_device_id');

            $table->string('printer_type', 32)
                ->default(PrinterTypeEnum::Thermal->value)
                ->comment('Tipo de tecnología de la impresora (thermal, ticket, 2d, 3d)');

            $table->boolean('is_active')
                ->default(true)
                ->comment('Indica si la impresora está habilitada para recibir nuevos trabajos');

            $table->string('status', 32)
                ->default(PrinterStatusEnum::Offline->value)
                ->comment('Último estado reportado por el microcontrolador');

            $table->jsonb('supported_formats')
                ->default(json_encode([PrintJobFormatEnum::Text->value, PrintJobFormatEnum::Escpos->value]))
                ->comment('Formatos de payload aceptados por esta impresora (text, escpos, json, markdown, gcode)');

            $table->string('default_format', 32)
                ->default(PrintJobFormatEnum::Text->value)
                ->comment('Formato predeterminado si el emisor no especifica uno al encolar');

            $table->unsignedInteger('max_payload_kb')
                ->default(64)
                ->comment('Tamaño máximo de payload en KB para proteger microcontroladores de desbordamiento de RAM');

            $table->unsignedInteger('total_prints_count')
                ->default(0)
                ->comment('Contador global acumulado de impresiones exitosas confirmadas en esta impresora (odómetro)');

            $table->timestamp('last_seen_at')
                ->nullable()
                ->index()
                ->comment('Último momento de contacto o sondeo recibido del microcontrolador');
        });

        // 4. Ampliar printer_stack con estado, reintentos, odómetro e índice atómico
        Schema::table('printer_stack', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();

            $table->string('status', 32)
                ->default(PrintJobStatusEnum::Pending->value)
                ->index()
                ->comment('Estado del trabajo en cola (pending, processing, completed, failed, cancelled)');

            $table->string('format', 32)
                ->default(PrintJobFormatEnum::Text->value)
                ->comment('Formato del contenido (text, escpos, markdown, json, gcode)');

            $table->integer('priority')
                ->default(0)
                ->comment('Prioridad de ejecución: mayor número se imprime antes');

            $table->unsignedSmallInteger('attempts')
                ->default(0)
                ->comment('Número de intentos de ejecución realizados');

            $table->unsignedInteger('print_count')
                ->default(0)
                ->comment('Número de veces que el microcontrolador ha confirmado la impresión exitosa de este trabajo');

            $table->boolean('is_favorite')
                ->default(false)
                ->index()
                ->comment('Marca el trabajo como plantilla / favorito para reimpresión recurrente');

            $table->text('error_message')
                ->nullable()
                ->comment('Mensaje o traza de error en caso de fallo');

            $table->timestamp('printed_at')
                ->nullable()
                ->comment('Fecha y hora en que se confirmó la última impresión física con éxito');

            $table->index(['printer_id', 'status', 'priority', 'created_at'], 'printer_stack_queue_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Revertir campos de printer_stack
        Schema::table('printer_stack', function (Blueprint $table) {
            $table->dropIndex('printer_stack_queue_idx');
            $table->dropIndex(['status']);
            $table->dropIndex(['is_favorite']);
            $table->dropColumn([
                'status',
                'format',
                'priority',
                'attempts',
                'print_count',
                'is_favorite',
                'error_message',
                'printed_at',
            ]);
        });

        // 2. Revertir campos de printers
        Schema::table('printers', function (Blueprint $table) {
            $table->dropIndex('printers_hardware_device_id_index');
            $table->dropIndex(['last_seen_at']);
            $table->dropColumn([
                'printer_type',
                'is_active',
                'status',
                'supported_formats',
                'default_format',
                'max_payload_kb',
                'total_prints_count',
                'last_seen_at',
            ]);
            $table->unsignedBigInteger('hardware_device_id')->nullable()->change();
        });

        // 3. Recrear printer_available_types
        Schema::create('printer_available_types', function (Blueprint $table) {
            $table->comment('Tipos de impresoras');
            $table->bigIncrements('id')->comment('Identificador único');
            $table->string('name', 255)->comment('Nombre del tipo de impresora');
            $table->string('slug', 255)->comment('Nombre del tipo de impresora');
            $table->text('description')->nullable()->comment('Descripción');
            $table->timestamps()->comment('Marcas de tiempo de creación y actualización');
        });

        // 4. Restaurar columnas en printers
        Schema::table('printers', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()
                ->comment('Relación con el usuario propietario de la impresora');
            $table->foreign('user_id')->references('id')->on('users')
                ->onUpdate('CASCADE')->onDelete('CASCADE');

            $table->unsignedBigInteger('printer_type_id')->nullable()
                ->comment('Relación el tipo de impresora');
            $table->foreign('printer_type_id')->references('id')->on('printer_available_types')
                ->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }
};
