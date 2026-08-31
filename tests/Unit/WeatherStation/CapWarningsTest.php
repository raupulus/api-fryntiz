<?php

declare(strict_types=1);

namespace Tests\Unit\WeatherStation;

use App\Support\WeatherStation\CapWarnings;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El lector del paquete de avisos CAP de AEMET.
 *
 * Se prueba con un `tar` construido a mano porque es la **única** forma de
 * comprobarlo: los avisos sólo existen cuando hay temporal, y un test que
 * dependa de que hoy haya viento en Cádiz no prueba nada el resto del año. Los
 * XML de aquí son los del ejemplo verificado en
 * `docs/apis/aemet/04-avisos-y-riesgos.md`, recortados.
 */
class CapWarningsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('aemet.warnings.zones', ['611103', '611104']);
        config()->set('aemet.warnings.discard_severity', ['Minor']);
        config()->set('aemet.warnings.valid_statuses', ['Actual']);
        config()->set('aemet.warnings.language', 'es-ES');
    }

    /**
     * Un XML CAP con los dos bloques `<info>` que manda AEMET siempre.
     */
    private function xmlFixture(
        string $zone = '611103',
        string $severity = 'Moderate',
        string $level = 'amarillo',
        string $status = 'Actual',
        int $areas = 1,
    ): string {
        $areaBlock = '';

        for ($i = 0; $i < $areas; $i++) {
            $code = $i === 0 ? $zone : '611104';
            $name = $i === 0 ? 'Litoral gaditano' : 'Estrecho';
            $areaBlock .= <<<XML
                <area>
                  <areaDesc>{$name}</areaDesc>
                  <polygon>36.7,-6.4 36.6,-6.3 36.7,-6.4</polygon>
                  <geocode>
                    <valueName>AEMET-Meteoalerta zona</valueName>
                    <value>{$code}</value>
                  </geocode>
                </area>
            XML;
        }

        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <alert xmlns="urn:oasis:names:tc:emergency:cap:1.2">
          <identifier>2.49.0.0.724.0.ES.20260829090336.{$zone}PRP12616</identifier>
          <sender>http://www.aemet.es</sender>
          <sent>2026-08-29T09:03:36-00:00</sent>
          <status>{$status}</status>
          <msgType>Update</msgType>
          <scope>Public</scope>
          <info>
            <language>es-ES</language>
            <category>Met</category>
            <event>Aviso de lluvias de nivel {$level}</event>
            <responseType>Monitor</responseType>
            <urgency>Future</urgency>
            <severity>{$severity}</severity>
            <certainty>Likely</certainty>
            <eventCode>
              <valueName>AEMET-Meteoalerta fenomeno</valueName>
              <value>PR;Lluvias</value>
            </eventCode>
            <effective>2026-08-29T11:03:36+02:00</effective>
            <onset>2026-08-30T06:00:00+02:00</onset>
            <expires>2026-08-30T17:59:59+02:00</expires>
            <headline>Aviso de lluvias de nivel {$level}. Litoral gaditano</headline>
            <description>Precipitación acumulada en una hora: 15 mm.</description>
            <instruction>Esté atento.</instruction>
            <parameter>
              <valueName>AEMET-Meteoalerta nivel</valueName>
              <value>{$level}</value>
            </parameter>
            <parameter>
              <valueName>AEMET-Meteoalerta probabilidad</valueName>
              <value>40%-70%</value>
            </parameter>
            <parameter>
              <valueName>AEMET-Meteoalerta parametro</valueName>
              <value>P1;Precipitación acumulada en una hora;15 mm</value>
            </parameter>
        {$areaBlock}
          </info>
          <info>
            <language>en-GB</language>
            <event>Rain warning</event>
            <severity>{$severity}</severity>
            <headline>Rain warning. Litoral gaditano</headline>
            <description>Accumulated rainfall in one hour: 15 mm.</description>
        {$areaBlock}
          </info>
        </alert>
        XML;
    }

    /**
     * Construye un `tar` POSIX a mano.
     *
     * No se usa `PharData` para escribirlo porque `phar.readonly` está activo
     * por defecto en CLI y el test no puede depender de la configuración de PHP
     * de quien lo ejecute.
     *
     * @param  array<string,string>  $files  nombre => contenido
     */
    private function tarFixture(array $files): string
    {
        $tar = '';

        foreach ($files as $name => $content) {
            $header = str_pad($name, 100, "\0");            // name
            $header .= str_pad('0000644', 8, "\0");            // mode
            $header .= str_pad('0000000', 8, "\0");            // uid
            $header .= str_pad('0000000', 8, "\0");            // gid
            $header .= str_pad(decoct(strlen($content)), 11, '0', STR_PAD_LEFT)."\0"; // size
            $header .= str_pad(decoct(time()), 11, '0', STR_PAD_LEFT)."\0";             // mtime
            $header .= str_repeat(' ', 8);                     // checksum, en blanco para calcularlo
            $header .= '0';                                    // typeflag: fichero normal
            $header .= str_repeat("\0", 100);                  // linkname
            $header .= "ustar\0";                              // magic  ← la firma del offset 257
            $header .= '00';                                   // version
            $header .= str_repeat("\0", 32 + 32 + 8 + 8 + 155); // uname, gname, dev*, prefix
            $header = str_pad($header, 512, "\0");

            $suma = 0;
            for ($i = 0; $i < 512; $i++) {
                $suma += ord($header[$i]);
            }
            $header = substr_replace(
                $header,
                str_pad(decoct($suma), 6, '0', STR_PAD_LEFT)."\0 ",
                148,
                8
            );

            $tar .= $header;
            $tar .= str_pad($content, (int) (ceil(strlen($content) / 512) * 512), "\0");
        }

        return $tar.str_repeat("\0", 1024);
    }

    #[Test]
    public function recognises_a_tar_by_its_signature(): void
    {
        $this->assertTrue(CapWarnings::isTar($this->tarFixture(['a.xml' => '<a/>'])));

        // AEMET responde 200 con una página de error más a menudo de lo que
        // debería. Sin esta comprobación, PharData lanza y se pierde la
        // ejecución entera por un cuerpo que ni siquiera era un paquete.
        $this->assertFalse(CapWarnings::isTar('<html><body>Error</body></html>'));
        $this->assertFalse(CapWarnings::isTar(''));
    }

    /**
     * El fallo que se comía los avisos: `simplexml` colapsa el hijo único, y un
     * aviso real suele tener UNA zona. El `foreach` recorría entonces los campos
     * del área en vez de las áreas, y el aviso se perdía sin un solo error.
     */
    #[Test]
    public function a_warning_for_a_single_zone_is_not_lost(): void
    {
        $warnings = CapWarnings::fromXml($this->xmlFixture(areas: 1));

        $this->assertCount(1, $warnings, 'Un aviso con una única zona se ha perdido por el camino.');
        $this->assertSame('611103', $warnings[0]['geocode']);
        $this->assertSame('Litoral gaditano', $warnings[0]['name']);
    }

    #[Test]
    public function a_warning_for_several_zones_yields_one_row_per_zone(): void
    {
        $warnings = CapWarnings::fromXml($this->xmlFixture(areas: 2));

        $this->assertCount(2, $warnings);
        $this->assertSame(['611103', '611104'], array_column($warnings, 'geocode'));
    }

    /**
     * Cada XML trae el mismo aviso dos veces, en `es-ES` y en `en-GB`. Recorrer
     * los dos lo duplicaba y, con el `updateOrCreate` por zona y fecha, acababa
     * ganando el inglés.
     */
    #[Test]
    public function keeps_only_the_spanish_block(): void
    {
        $warnings = CapWarnings::fromXml($this->xmlFixture(areas: 1));

        $this->assertCount(1, $warnings, 'El aviso se ha guardado una vez por idioma.');
        $this->assertSame('es-ES', $warnings[0]['language']);
        $this->assertSame('Aviso de lluvias de nivel amarillo', $warnings[0]['event']);
        $this->assertStringContainsString('Precipitación', (string) $warnings[0]['description']);
    }

    /**
     * El nivel verde lo suprimió el Plan Meteoalerta en 2022: no es un aviso,
     * es la ausencia de aviso. Y eran 177 de los 252 mensajes del paquete
     * nacional.
     */
    #[Test]
    public function discards_green_warnings(): void
    {
        $this->assertSame([], CapWarnings::fromXml($this->xmlFixture(severity: 'Minor', level: 'verde')));
    }

    #[Test]
    public function discards_test_messages(): void
    {
        $this->assertSame([], CapWarnings::fromXml($this->xmlFixture(status: 'Test')));
    }

    #[Test]
    public function discards_zones_that_are_not_ours(): void
    {
        config()->set('aemet.warnings.zones', ['711502']);

        $this->assertSame([], CapWarnings::fromXml($this->xmlFixture()));
    }

    #[Test]
    public function accepts_a_zone_by_prefix(): void
    {
        config()->set('aemet.warnings.zones', ['6111']);

        $this->assertCount(1, CapWarnings::fromXml($this->xmlFixture()));
    }

    /**
     * El nivel, el fenómeno y las fechas de vigencia son lo que decide si un
     * aviso se enseña y con qué color. Antes no se guardaba ninguno de los tres.
     */
    #[Test]
    public function stores_the_level_the_phenomenon_and_the_validity(): void
    {
        $warning = CapWarnings::fromXml($this->xmlFixture())[0];

        $this->assertSame('Moderate', $warning['severity']);
        $this->assertSame('amarillo', $warning['level']);
        $this->assertSame('PR;Lluvias', $warning['event_code']);
        $this->assertSame('40%-70%', $warning['probability']);
        $this->assertSame('P1;Precipitación acumulada en una hora;15 mm', $warning['parameter']);
        $this->assertNotNull($warning['onset_at']);
        $this->assertNotNull($warning['expires_at']);
    }

    /**
     * `sent` llega en UTC y `onset`/`expires` en hora local. Compararlos sin
     * normalizar da errores de una o dos horas según la época del año (D100).
     */
    #[Test]
    public function normalises_every_date_to_utc(): void
    {
        $warning = CapWarnings::fromXml($this->xmlFixture())[0];

        $this->assertSame('UTC', $warning['read_at']->timezone->getName());
        $this->assertSame('2026-08-29 09:03:36', $warning['read_at']->format('Y-m-d H:i:s'));

        // 06:00 en hora peninsular de verano son las 04:00 UTC.
        $this->assertSame('UTC', $warning['onset_at']->timezone->getName());
        $this->assertSame('2026-08-30 04:00:00', $warning['onset_at']->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function always_stores_polygons_as_a_list(): void
    {
        $warning = CapWarnings::fromXml($this->xmlFixture())[0];

        $this->assertIsArray($warning['polygons']);
        $this->assertCount(1, $warning['polygons']);
        $this->assertStringStartsWith('36.7,-6.4', $warning['polygons'][0]);
    }

    #[Test]
    public function reads_the_whole_package_and_leaves_no_rubbish(): void
    {
        $before = glob(sys_get_temp_dir().'/aemet-avisos-*') ?: [];

        $warnings = CapWarnings::fromTar($this->tarFixture([
            'Z_CAP_C_LEMM_20260829090336_AFAZ611103PRP12616.xml' => $this->xmlFixture(),
            'Z_CAP_C_LEMM_20260829090336_AFAZ611104PRP12616.xml' => $this->xmlFixture(zone: '611104'),
            'leeme.txt' => 'esto no es un XML y no debe estorbar',
        ]));

        $this->assertCount(2, $warnings);

        $despues = glob(sys_get_temp_dir().'/aemet-avisos-*') ?: [];
        $this->assertSame(
            count($before),
            count($despues),
            'El lector ha dejado el paquete descomprimido en el temporal.'
        );
    }

    #[Test]
    public function a_download_that_is_not_a_tar_does_not_blow_up(): void
    {
        $this->assertSame([], CapWarnings::fromTar('<html>503 Service Unavailable</html>'));
    }
}
