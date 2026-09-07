<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La tensión del campo solar, que no es la de la instalación.
 *
 * `energy_systems.nominal_voltage` es la del **banco de baterías**: es lo que
 * define el sistema («Casa 24V»), y en las dos instalaciones reales vale 12 V.
 * Pero el lado del panel puede estar a otra tensión, y de hecho lo está:
 *
 * | Instalación | Panel | Batería |
 * |---|---|---|
 * | Renogy Rover 20 LI | **24 V** (medido hasta 43,9 V) | 12 V (11,0-14,3 V) |
 * | Sunix 20A | **12 V** (llega a 18-20 V) | 12 V (hasta 13,8 V) |
 *
 * Sin este campo, el único sitio donde vivía esa diferencia era un flag del
 * comando que la rellena, o sea la memoria de quien lo ejecute. Y equivocarse
 * no da error: sólo hace que los vatios de respaldo del Sunix salgan al doble el
 * día que su controlador deje de mandar la tensión medida.
 *
 * `nullable` a propósito: un sistema sin paneles —un banco de baterías que se
 * carga de la red— no tiene tensión de campo solar, y ahí `NULL` significa
 * exactamente eso y no «no lo hemos rellenado».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('energy_systems', function (Blueprint $table) {
            $table->decimal('pv_nominal_voltage', 8, 2)
                ->nullable()
                ->after('nominal_voltage')
                ->comment('Tensión nominal del campo solar (V). Distinta de `nominal_voltage`, que es la del banco de baterías. NULL si la instalación no tiene paneles.');
        });
    }

    public function down(): void
    {
        Schema::table('energy_systems', function (Blueprint $table) {
            $table->dropColumn('pv_nominal_voltage');
        });
    }
};
