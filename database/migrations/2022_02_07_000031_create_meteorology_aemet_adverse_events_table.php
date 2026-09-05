<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateMeteorologyAemetAdverseEvents.
 *
 * Tabla para almacenar los fenómenos meteorológicos adversos.
 */
class CreateMeteorologyAemetAdverseEventsTable extends Migration
{
    private $tableName = 'meteorology_aemet_adverse_events';

    private $tableComment = 'Datos de fenómenos meteorológicos adversos';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create($this->tableName, function (Blueprint $table) {
            $table->comment('Datos de fenómenos meteorológicos adversos');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único');

            $table->string('name', 255)
                ->comment('Nombre de la zona sobre la que guardamos los datos');

            $table->string('slug', 255)
                ->comment('Slug creado a partir del nombre recibido en la api, con este se identificará la zona para las consultas');

            $table->text('others_fields_json')
                ->nullable()
                ->comment('Estos son campos que no están definidos en la api pero pueden llegar, hasta ahora no hay forma de identificar un fenómeno con valores númericos para interpretarlos');

            $table->timestamp('read_at')->comment('Columna read at');
            $table->timestamps()->comment('Marcas de tiempo de creación y actualización');

            // ── Identificación ────────────────────────────────────────────────
            $table->string('identifier', 255)->nullable()
                ->comment('Identificador único del aviso en CAP. Es lo que permite deduplicar.');
            $table->string('geocode', 16)->nullable()
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
        DB::statement("COMMENT ON TABLE {$this->tableName} IS '{$this->tableComment}'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->tableName);
    }
}
