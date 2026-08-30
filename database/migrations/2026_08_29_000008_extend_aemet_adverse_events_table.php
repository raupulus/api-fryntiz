<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los avisos de AEMET, con lo que un aviso es.
 *
 * La tabla guardaba **nombre de zona, polígono y fecha de emisión**, y nada más.
 * Con eso no se puede saber si un aviso es amarillo o rojo, de qué fenómeno es,
 * cuándo empieza ni cuándo caduca: justo lo único que hace falta para decidir si
 * se enseña o no. En V1 quedó escrito como TODO:
 *
 * > «Faltaría "type" de alerta y grado de peligro.»
 *
 * Todo lo que entra aquí es CAP 1.2 estándar más los tres parámetros propios de
 * AEMET, verificados en vivo el 2026-08-26 sobre 252 ficheros del paquete
 * nacional (`docs/apis/aemet/04-avisos-y-riesgos.md`).
 *
 * Dos cambios que no son añadir columnas:
 *
 * - **`geocode`**: el helper ya lo leía del XML y lo metía en el array, pero no
 *   había columna, así que `updateOrCreate()` lo tiraba en silencio. Es el
 *   código de zona, el único identificador estable —el nombre cambia— y el
 *   mismo `zona_comarcal` que devuelve el maestro de municipios.
 * - **`polygon` → `polygons`**: un `<area>` puede traer varios polígonos, y la
 *   columna era `text`: con más de uno se guardaba un array casteado a cadena.
 *   Ahora es una lista, siempre, venga uno o vengan cinco.
 *
 * Y la clave natural pasa a ser `identifier` + `geocode`. Antes era zona y fecha
 * de emisión, así que dos avisos distintos de la misma zona emitidos en el mismo
 * segundo —viento y lluvia, que es lo normal en un temporal— se machacaban entre
 * ellos y sólo quedaba uno.
 *
 * No hay nada que migrar: el paquete de AEMET es el estado completo y vigente,
 * no un incremento, así que la siguiente ejecución del comando lo repuebla.
 */
return new class extends Migration
{
    private string $tableName = 'meteorology_aemet_adverse_events';

    public function up(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            // ── Identificación ────────────────────────────────────────────────
            $table->string('identifier', 255)->nullable()->after('id')
                ->comment('Identificador único del aviso en CAP. Es lo que permite deduplicar.');
            $table->string('geocode', 16)->nullable()->after('slug')
                ->comment('Código de zona: CCAA(2)+provincia INE(2)+comarca(2), con sufijo C si es la costera.');
            $table->string('language', 16)->nullable()
                ->comment('Idioma del bloque <info> guardado. Cada XML trae es-ES y en-GB con el mismo aviso.');
            $table->string('status', 32)->nullable()
                ->comment('Actual = operativo. Test = prueba, no se guarda.');
            $table->string('msg_type', 32)->nullable()
                ->comment('Alert | Update | Cancel. AEMET no emite Cancel: retira con un aviso que nace caducado.');

            // ── Qué avisa y con qué gravedad ──────────────────────────────────
            $table->string('event', 255)->nullable()
                ->comment('«Aviso de lluvias de nivel amarillo». Ya viene redactado y con el nivel dentro.');
            $table->string('event_code', 64)->nullable()
                ->comment('Fenómeno, formato «PR;Lluvias». OJO: usa códigos DISTINTOS de los del parámetro.');
            $table->string('severity', 32)->nullable()
                ->comment('Minor=verde (no es aviso) | Moderate=amarillo | Severe=naranja | Extreme=rojo.');
            $table->string('level', 32)->nullable()
                ->comment('Nivel de AEMET: amarillo, naranja, rojo. Correlación exacta con severity.');
            $table->string('urgency', 32)->nullable()
                ->comment('Immediate = ya está ocurriendo | Expected | Future.');
            $table->string('certainty', 32)->nullable()
                ->comment('Observed | Likely | Possible.');
            $table->string('response_type', 32)->nullable()
                ->comment('Monitor, o None en los verdes.');
            $table->string('probability', 32)->nullable()
                ->comment('Sólo tres valores posibles: 10%-40%, 40%-70%, mayor 70%.');
            $table->string('parameter', 255)->nullable()
                ->comment('Formato «código;descripción;umbral». El umbral cambia por zona y época: no es constante.');

            // ── Textos listos para mostrar ────────────────────────────────────
            $table->string('headline', 512)->nullable()
                ->comment('Construido por AEMET como «evento. zona».');
            $table->text('description')->nullable()
                ->comment('Parámetro, valor y unidad. Ausente en los verdes.');
            $table->text('instruction')->nullable()
                ->comment('Qué hacer. Depende del nivel, no del fenómeno. Ausente en los verdes.');

            // ── Vigencia ──────────────────────────────────────────────────────
            //
            // `read_at` ya guarda el `sent`, que viene en UTC. Estas tres llegan
            // en hora LOCAL y se normalizan a UTC al leerlas (D100).
            $table->timestamp('effective_at')->nullable()
                ->comment('Desde cuándo vale el mensaje.');
            $table->timestamp('onset_at')->nullable()
                ->comment('Cuándo empieza el fenómeno.');
            $table->timestamp('expires_at')->nullable()
                ->comment('Cuándo deja de valer. Filtrar SIEMPRE por aquí: AEMET retira avisos emitiendo uno ya caducado.');

            $table->json('polygons')->nullable()
                ->comment('Lista de polígonos del área, pares «lat,lon» separados por espacios (al revés que GeoJSON).');

            $table->unique(['identifier', 'geocode'], 'aemet_avisos_identifier_zona_uk');
            $table->index(['geocode', 'expires_at'], 'aemet_avisos_zona_expira_idx');
            $table->index('expires_at', 'aemet_avisos_expira_idx');
        });

        Schema::table($this->tableName, function (Blueprint $table) {
            if (Schema::hasColumn($this->tableName, 'polygon')) {
                $table->dropColumn('polygon');
            }
        });
    }

    public function down(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropUnique('aemet_avisos_identifier_zona_uk');
            $table->dropIndex('aemet_avisos_zona_expira_idx');
            $table->dropIndex('aemet_avisos_expira_idx');

            $table->dropColumn([
                'identifier', 'geocode', 'language', 'status', 'msg_type',
                'event', 'event_code', 'severity', 'level', 'urgency',
                'certainty', 'response_type', 'probability', 'parameter',
                'headline', 'description', 'instruction',
                'effective_at', 'onset_at', 'expires_at', 'polygons',
            ]);

            $table->text('polygon')->nullable()
                ->comment('Array de Coordenadas para polígonos');
        });
    }
};
