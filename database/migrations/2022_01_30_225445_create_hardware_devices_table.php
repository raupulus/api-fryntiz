<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dispositivos de hardware: inventario y último estado conocido que reporta el
 * propio cacharro (temperatura, tensión, CPU, RAM, disco, batería…).
 */
return new class extends Migration
{
    private string $tableName = 'hardware_devices';

    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->comment('Dispositivos de hardware');

            $table->bigIncrements('id')->comment('Identificador único');

            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->comment('Usuario dueño del registro.');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table->unsignedBigInteger('image_id')
                ->nullable()
                ->comment('Relación con la imagen asociada');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');

            $table->unsignedBigInteger('hardware_type_id')
                ->nullable()
                ->comment('Relación con el tipo de hardware asociado');
            $table->foreign('hardware_type_id')
                ->references('id')->on('hardware_types')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');

            $table->unsignedBigInteger('referred_thing_id')
                ->nullable()
                ->comment('Relación con el dispositivo afiliado');
            $table->foreign('referred_thing_id')
                ->references('id')->on('referred_things')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');

            $table->string('name', 255)
                ->nullable()
                ->comment('Nombre real del dispositivo, EJ: Raspberry Pi 4b+');
            $table->string('name_friendly', 255)
                ->nullable()
                ->comment('Nombre amistoso para reconocerlo EJ: Raspberry en azotea');
            $table->string('location_type', 20)
                ->default('indoor')
                ->comment('Ubicación física del hardware: indoor (interior) u outdoor (exterior). Por defecto interior.');
            $table->string('zone', 100)
                ->nullable()
                ->comment('Zona/ubicación concreta del hardware, EJ: Azotea, Salón, Jardín.');
            $table->string('ref', 255)
                ->nullable()
                ->comment('Referencia del dispositivo');
            $table->string('brand', 255)
                ->nullable()
                ->comment('Marca del fabricante, EJ: Apple');
            $table->string('model', 255)
                ->nullable()
                ->comment('Modelo del dispositivo, EJ: 4b+');
            $table->string('software_version', 255)
                ->nullable()
                ->comment('Versión del software del dispositivo');
            $table->string('hardware_version', 255)
                ->nullable()
                ->comment('Versión del hardware del dispositivo');
            $table->string('serial_number', 255)
                ->nullable()
                ->comment('Número de serie que declara el controlador.');
            $table->string('battery_type', 255)
                ->nullable()
                ->comment('Tipo de batería, EJ: Gel, li ion');
            $table->integer('battery_nominal_capacity')
                ->nullable()
                ->comment('Capacidad nominal de la batería en mAh, EJ: 4200');
            $table->string('url_company', 255)
                ->nullable()
                ->comment('Enlace a la página de la empresa fabricante');
            $table->text('description')
                ->nullable()
                ->comment('Descripción del dispositivo.');
            $table->dateTime('buy_at')
                ->nullable()
                ->comment('Fecha de compra del dispositivo');
            $table->timestamp('last_seen_at')
                ->nullable()
                ->comment('Última vez que se vio el dispositivo');
            $table->string('ip_local', 255)
                ->nullable()
                ->comment('Ip local del dispositivo');
            $table->string('ip_public', 255)
                ->nullable()
                ->comment('Ip pública del dispositivo');

            // Último estado conocido que reporta el propio cacharro.
            $table->decimal('temp', 6, 2)
                ->nullable()
                ->comment('Última temperatura conocida del dispositivo en grados Celsius.');
            $table->decimal('voltage', 8, 3)
                ->nullable()
                ->comment('Última tensión conocida del dispositivo en voltios (EJ: batería por divisor de tensión).');
            $table->unsignedSmallInteger('battery_level')
                ->nullable()
                ->comment('Último nivel de batería conocido en porcentaje (0-100).');
            $table->decimal('cpu', 5, 2)
                ->nullable()
                ->comment('Último uso de CPU conocido en porcentaje (0-100).');
            $table->decimal('disk', 5, 2)
                ->nullable()
                ->comment('Último uso de disco conocido en porcentaje (0-100).');
            $table->unsignedBigInteger('uptime')
                ->nullable()
                ->comment('Último tiempo de actividad conocido del dispositivo en segundos.');
            $table->json('extra')
                ->nullable()
                ->comment('Métricas de estado adicionales del dispositivo en formato JSON (RAM, procesos, etc.).');
            $table->decimal('battery_voltage', 8, 3)
                ->nullable()
                ->comment('Tensión de la batería del propio dispositivo (V).');
            $table->decimal('ram', 5, 2)
                ->nullable()
                ->comment('Último uso de memoria conocido en porcentaje (0-100).');
            $table->decimal('battery_nominal_voltage', 8, 3)
                ->nullable()
                ->comment('Tensión nominal de diseño de la batería (V), EJ: 12.');

            $table->timestamps();
            $table->softDeletes()->comment('Marca de tiempo para borrado lógico');

            // Patrón de consulta: filtrar/agrupar por tipo de ubicación.
            $table->index('location_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->tableName);
    }
};
