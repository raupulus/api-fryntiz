<?php

declare(strict_types=1);

namespace App\Support\WeatherStation;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PharData;
use Throwable;

use function is_array;
use function is_string;

/**
 * Lector del paquete de avisos de fenómenos adversos de AEMET (CAP 1.2).
 *
 * Vive aparte del helper porque **no necesita red**: recibe los bytes del `tar`
 * y devuelve filas. Así se puede probar con un paquete de mentira, que es la
 * única forma de comprobar un formato que sólo trae datos cuando hay temporal.
 *
 * Todo lo que hay aquí sale de la verificación en vivo del 2026-08-26 recogida
 * en `docs/apis/aemet/04-avisos-y-riesgos.md` (56 XML de una comunidad y 252 del
 * paquete nacional), no de la especificación a secas.
 *
 * ## Lo que hacía mal la versión anterior
 *
 * 1. **Se comía los avisos de una sola zona.** `simplexml` + `json_encode`
 *    colapsa un hijo único en un objeto y varios en una lista: con dos `<area>`
 *    llega `[{...},{...}]` y con una llega `{...}`. El `foreach` sobre el
 *    segundo caso recorre los CAMPOS del área, no las áreas, y el aviso se
 *    perdía entero sin un solo error. Un aviso real suele tener una zona.
 * 2. **Guardaba el aviso dos veces, y en inglés.** Cada XML trae dos bloques
 *    `<info>`, `es-ES` y `en-GB`, con el mismo aviso. Al recorrer los dos, el
 *    `updateOrCreate` posterior machacaba el texto español con el inglés.
 * 3. **Guardaba los verdes.** 177 de 252 mensajes del paquete nacional son
 *    `severity = Minor`, que el Plan Meteoalerta suprimió como nivel de aviso en
 *    2022: son la ausencia de aviso.
 * 4. **Tiraba el nivel, el fenómeno y las fechas de vigencia.** Sólo se
 *    guardaba nombre de zona, polígono y fecha de emisión, así que no había
 *    forma de saber si un aviso era amarillo o rojo, de qué era, ni cuándo
 *    empezaba. Era el TODO que quedó escrito en V1.
 * 5. **Podía matar el proceso.** Un `exit()` dentro del helper si no se podía
 *    crear el directorio temporal.
 */
final class CapWarnings
{
    /**
     * `ustar` en el offset 257 es la firma del formato tar POSIX.
     *
     * Hace falta comprobarlo porque el `Content-Type` de AEMET es
     * `application/x-gtar`, que sugiere gzip, y el paquete NO va comprimido; y
     * porque la API responde 200 con una página de error más a menudo de lo que
     * debería. Sin esta comprobación, `PharData` lanza y se pierde la ejecución
     * entera por un cuerpo que ni siquiera era un paquete.
     */
    private const TAR_SIGNATURE_OFFSET = 257;

    private const TAR_SIGNATURE = 'ustar';

    /**
     * Lee el paquete y devuelve una fila por aviso y zona.
     *
     * @param  string  $bytes  Contenido del `tar`, sin tocar.
     * @return list<array<string,mixed>>
     */
    public static function fromTar(string $bytes): array
    {
        if (! self::isTar($bytes)) {
            Log::warning('AEMET avisos: la descarga no es un tar (sin firma «ustar» en el offset 257).', [
                'bytes' => strlen($bytes),
            ]);

            return [];
        }

        // Un directorio por ejecución: dos comandos solapados escribiendo en el
        // mismo sitio se pisaban los XML, y el paquete anterior se quedaba ahí
        // para que lo leyera la ejecución siguiente.
        $directory = sys_get_temp_dir().'/aemet-avisos-'.bin2hex(random_bytes(8));
        $paquete = $directory.'/avisos.tar';

        if (! @mkdir($directory, 0700, true) && ! is_dir($directory)) {
            Log::error('AEMET avisos: no se ha podido crear el directorio temporal.', [
                'directorio' => $directory,
            ]);

            return [];
        }

        try {
            if (file_put_contents($paquete, $bytes) === false) {
                Log::error('AEMET avisos: no se ha podido escribir el paquete temporal.');

                return [];
            }

            $tar = new PharData($paquete);
            $tar->extractTo($directory, null, true);

            $warnings = [];

            foreach (self::xmlFromDirectory($directory) as $path) {
                $content = file_get_contents($path);

                if ($content === false) {
                    continue;
                }

                foreach (self::fromXml($content) as $warning) {
                    $warnings[] = $warning;
                }
            }

            return $warnings;
        } catch (Throwable $e) {
            Log::error('AEMET avisos: fallo leyendo el paquete.', ['message' => $e->getMessage()]);

            return [];
        } finally {
            // Pase lo que pase, no se deja basura: el paquete nacional son
            // 3,4 MB y esto corre cada media hora.
            self::deleteDirectory($directory);
        }
    }

    /**
     * ¿Los bytes son un tar POSIX?
     */
    public static function isTar(string $bytes): bool
    {
        return strlen($bytes) > self::TAR_SIGNATURE_OFFSET + 5
            && str_starts_with(substr($bytes, self::TAR_SIGNATURE_OFFSET, 5), self::TAR_SIGNATURE);
    }

    /**
     * Convierte un XML CAP en las filas que le corresponden: una por zona.
     *
     * @return list<array<string,mixed>>
     */
    public static function fromXml(string $xml): array
    {
        $alert = self::xmlToArray($xml);

        if ($alert === null) {
            return [];
        }

        // `Test` y `Exercise` son mensajes de prueba; sólo `Actual` es operativo.
        $status = self::text($alert['status'] ?? null);
        $estadosValidos = (array) config('aemet.warnings.valid_statuses', ['Actual']);

        if ($status !== null && ! in_array($status, $estadosValidos, true)) {
            return [];
        }

        $emitidoEn = self::parseDate($alert['sent'] ?? null);

        if ($emitidoEn === null) {
            return [];
        }

        $info = self::blockInOurLanguage($alert);

        if ($info === null) {
            return [];
        }

        // El nivel verde (`Minor`) no es un aviso: el Plan Meteoalerta lo
        // suprimió en 2022. Y son la mayoría de los mensajes del paquete.
        $severity = self::text($info['severity'] ?? null);
        $descartar = (array) config('aemet.warnings.discard_severity', ['Minor']);

        if ($severity !== null && in_array($severity, $descartar, true)) {
            return [];
        }

        $parameters = self::valuePairs($info['parameter'] ?? null);
        $fenomeno = self::valuePairs($info['eventCode'] ?? null);
        $zonasAceptadas = (array) config('aemet.warnings.zones', []);

        $comun = [
            'identifier' => self::text($alert['identifier'] ?? null),
            'msg_type' => self::text($alert['msgType'] ?? null),
            'status' => $status,
            'read_at' => $emitidoEn,
            'language' => self::text($info['language'] ?? null),
            'event' => self::text($info['event'] ?? null),
            'event_code' => $fenomeno['AEMET-Meteoalerta fenomeno'] ?? null,
            'severity' => $severity,
            'urgency' => self::text($info['urgency'] ?? null),
            'certainty' => self::text($info['certainty'] ?? null),
            'response_type' => self::text($info['responseType'] ?? null),
            'level' => $parameters['AEMET-Meteoalerta nivel'] ?? null,
            'probability' => $parameters['AEMET-Meteoalerta probabilidad'] ?? null,
            'parameter' => $parameters['AEMET-Meteoalerta parametro'] ?? null,
            'headline' => self::text($info['headline'] ?? null),
            'description' => self::text($info['description'] ?? null),
            'instruction' => self::text($info['instruction'] ?? null),
            // `sent` viene en UTC y estas tres en hora LOCAL. Compararlas sin
            // normalizar da errores de una o dos horas según la época del año.
            'effective_at' => self::parseDate($info['effective'] ?? null),
            'onset_at' => self::parseDate($info['onset'] ?? null),
            'expires_at' => self::parseDate($info['expires'] ?? null),

            // Lo que AEMET mande y aquí no esté contemplado. Es para lo que
            // existía esta columna desde 2022 y ahora sí cumple su función: sólo
            // recoge lo NO mapeado, así que si un día aparece una etiqueta nueva
            // no se pierde y se ve en cuanto alguien mire una fila.
            'others_fields_json' => self::leftovers($info),
        ];

        $rows = [];

        // Un `<info>` trae VARIAS `<area>` —3,25 de media en la muestra—, y con
        // una sola `simplexml` no devuelve una lista. `asList()` lo normaliza.
        foreach (self::asList($info['area'] ?? null) as $area) {
            if (! is_array($area)) {
                continue;
            }

            $name = self::text($area['areaDesc'] ?? null);
            $zone = self::valuePairs($area['geocode'] ?? null)['AEMET-Meteoalerta zona'] ?? null;

            if ($name === null || $zone === null) {
                continue;
            }

            if (! self::isOurZone($zone, $zonasAceptadas)) {
                continue;
            }

            $rows[] = $comun + [
                'name' => $name,
                'slug' => Str::slug($name, '_'),
                'geocode' => $zone,
                // Un área puede traer varios polígonos, y siempre se guardan
                // como lista para que leerlos no dependa de cuántos vinieran.
                'polygons' => self::polygons($area['polygon'] ?? null),
            ];
        }

        return $rows;
    }

    /**
     * Las etiquetas de `<info>` que no tienen columna propia, en JSON.
     *
     * @param  array<string,mixed>  $info
     */
    private static function leftovers(array $info): ?string
    {
        // `senderName`, `web` y `contact` NO se excluyen a propósito: son los
        // metadatos de procedencia del aviso, y la nota legal de AEMET obliga a
        // conservarlos. Este producto no trae bloque `origen` como los JSON, así
        // que este es el único sitio donde quedan guardados.
        $mapeadas = [
            'language', 'category', 'event', 'eventCode', 'severity', 'urgency',
            'certainty', 'responseType', 'parameter', 'headline', 'description',
            'instruction', 'effective', 'onset', 'expires', 'area',
        ];

        $resto = array_diff_key($info, array_flip($mapeadas));

        if ($resto === []) {
            return null;
        }

        $json = json_encode($resto, JSON_UNESCAPED_UNICODE);

        return $json === false ? null : $json;
    }

    /**
     * ¿La zona está entre las que nos interesan?
     *
     * Se acepta el código exacto o un prefijo, para poder pedir «toda la
     * provincia de Cádiz» con `6111` sin listar sus comarcas una a una.
     *
     * @param  array<int,string>  $acceptedZones
     */
    private static function isOurZone(string $zone, array $acceptedZones): bool
    {
        // Sin lista configurada no se filtra: es mejor guardar de más que
        // tragarse el paquete entero en silencio.
        if ($acceptedZones === []) {
            return true;
        }

        foreach ($acceptedZones as $aceptada) {
            if (str_starts_with($zone, (string) $aceptada)) {
                return true;
            }
        }

        return false;
    }

    /**
     * El bloque `<info>` en el idioma que queremos.
     *
     * Cada XML trae dos, `es-ES` y `en-GB`, con el mismo aviso. Recorrer los dos
     * lo guarda por duplicado y, con un `updateOrCreate` por zona y fecha,
     * termina ganando el inglés.
     *
     * @param  array<string,mixed>  $alert
     * @return array<string,mixed>|null
     */
    private static function blockInOurLanguage(array $alert): ?array
    {
        $blocks = self::asList($alert['info'] ?? null);
        $locale = (string) config('aemet.warnings.language', 'es-ES');

        foreach ($blocks as $block) {
            if (is_array($block) && self::text($block['language'] ?? null) === $locale) {
                return $block;
            }
        }

        // Sin bloque en nuestro idioma se coge el primero: mejor el aviso en
        // inglés que ningún aviso.
        $first = $blocks[0] ?? null;

        return is_array($first) ? $first : null;
    }

    /**
     * Aplana los bloques `<valueName>`/`<value>` de CAP a `[nombre => valor]`.
     *
     * Los `valueName` de AEMET son literales largos —`AEMET-Meteoalerta nivel`,
     * no `Nivel`—, y quien busque el nombre corto no encuentra nada nunca.
     *
     * @return array<string,string>
     */
    private static function valuePairs(mixed $blocks): array
    {
        $parejas = [];

        foreach (self::asList($blocks) as $block) {
            if (! is_array($block)) {
                continue;
            }

            $name = self::text($block['valueName'] ?? null);
            $value = self::text($block['value'] ?? null);

            if ($name !== null && $value !== null) {
                $parejas[$name] = $value;
            }
        }

        return $parejas;
    }

    /**
     * Los polígonos de un área, siempre como lista.
     *
     * Formato de AEMET: pares `latitud,longitud` separados por espacios, anillo
     * cerrado. Ojo al orden: GeoJSON los quiere al revés.
     *
     * @return list<string>|null
     */
    private static function polygons(mixed $poligono): ?array
    {
        $polygons = [];

        foreach (self::asList($poligono) as $one) {
            if (is_string($one) && trim($one) !== '') {
                $polygons[] = trim($one);
            }
        }

        return $polygons === [] ? null : $polygons;
    }

    /**
     * Normaliza «uno o varios» a lista.
     *
     * `simplexml` + `json_encode` colapsa el hijo único: dos `<area>` dan
     * `[{...},{...}]` y una da `{...}`. Es el fallo que se comía los avisos de
     * una sola zona, que son justo los avisos de verdad.
     *
     * @return list<mixed>
     */
    private static function asList(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if (! is_array($value)) {
            return [$value];
        }

        // Lista de verdad: claves 0..n.
        if (array_is_list($value)) {
            return $value;
        }

        return [$value];
    }

    /**
     * Un nodo de `simplexml` puede llegar como cadena o como array vacío.
     */
    private static function text(mixed $value): ?string
    {
        if (is_string($value)) {
            $clean = trim($value);

            return $clean === '' ? null : $clean;
        }

        return null;
    }

    private static function parseDate(mixed $value): ?Carbon
    {
        $text = self::text($value);

        if ($text === null) {
            return null;
        }

        try {
            // A UTC siempre: `sent` llega en UTC y `onset`/`expires` en hora
            // local. Guardarlas como vienen es garantizarse una comparación
            // desfasada una o dos horas según la época del año (D100).
            return Carbon::parse($text)->utc();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * XML de CAP a array asociativo.
     *
     * @return array<string,mixed>|null
     */
    private static function xmlToArray(string $xml): ?array
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $element = simplexml_load_string($xml);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($element === false) {
            return null;
        }

        $json = json_encode($element);

        if ($json === false) {
            return null;
        }

        $array = json_decode($json, true);

        return is_array($array) && $array !== [] ? $array : null;
    }

    /**
     * Rutas de los XML extraídos, incluidos los que vengan en subdirectorios.
     *
     * @return list<string>
     */
    private static function xmlFromDirectory(string $directory): array
    {
        $rutas = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'xml') {
                $rutas[] = $file->getPathname();
            }
        }

        return $rutas;
    }

    private static function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }

        @rmdir($directory);
    }
}
