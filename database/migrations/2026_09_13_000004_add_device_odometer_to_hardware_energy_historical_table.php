<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La última lectura del odómetro del aparato, para poder medir su **avance**.
 *
 * ## El fallo que arregla
 *
 * El acumulado hacía dos cosas mal con el odómetro de un aparato:
 *
 * 1. **Lo guardaba con `max()`.** Si el total que ya teníamos era mayor que el
 *    que marca el aparato, el acumulado se quedaba congelado para siempre: el
 *    Rover lleva 524.497 Wh contados por nosotros desde 2022 y su registro
 *    Modbus marca 41.206, así que ninguna lectura suya volvería a mover la
 *    cifra en años.
 * 2. **Detectaba el reinicio comparando ese odómetro contra nuestro total.**
 *    Son dos números que no miden lo mismo —uno es lo que cuenta el aparato,
 *    el otro lo que hemos sumado nosotros—, así que un aparato cuyo registro
 *    marca menos que nuestra suma parece reiniciado en cuanto abre la boca. Es
 *    lo que pasó en producción el 13/09/2026 a las 12:10: la primera subida con
 *    el contrato nuevo partió el histórico del panel, del consumo y de la
 *    batería en dos sesiones sin que el controlador se hubiera reiniciado —su
 *    registro venía subiendo sin saltos desde el día 6—.
 *
 * ## Qué se guarda aquí
 *
 * El último valor que el aparato reportó de cada magnitud. Con él, el acumulado
 * suma **avances** (`odómetro_nuevo − odómetro_anterior`) en vez de sustituir
 * totales, y el reinicio se detecta comparando el odómetro contra sí mismo, que
 * es lo único con lo que se puede comparar.
 *
 * Se queda a `null` mientras el aparato no mande odómetro de esa magnitud. La
 * primera vez que lo manda se adopta como punto de partida **sin sumarlo**: no
 * hay forma de saber cuánto de lo que marca ya está contado en lo que tenemos.
 */
return new class extends Migration
{
    private string $tableName = 'hardware_energy_historical';

    public function up(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->decimal('energy_wh_device_total', 16, 4)
                ->nullable()
                ->after('energy_ah_source')
                ->comment('Último total de vatios-hora que reportó el aparato. Sirve para medir su avance y para saber si se ha reiniciado. NULL = nunca ha mandado odómetro de esta magnitud.');

            $table->decimal('energy_ah_device_total', 14, 4)
                ->nullable()
                ->after('energy_wh_device_total')
                ->comment('Último total de amperios-hora que reportó el aparato. Mismo uso que el de vatios-hora.');
        });
    }

    public function down(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropColumn(['energy_wh_device_total', 'energy_ah_device_total']);
        });
    }
};
