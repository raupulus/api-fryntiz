<?php

declare(strict_types=1);

namespace App\Support\WeatherStation;

/**
 * Catálogo de localidades y provincias soportadas por el módulo de información
 * meteorológica y mareas de fuentes oficiales (`/api/v2/weather-station-official`).
 *
 * Centraliza los códigos de AEMET (municipio INE, playa, estación de observación
 * e índice UV) y los parámetros marinos de referencia para no dispersar literales
 * ni requerir peticiones adicionales a APIs externas.
 */
final class OfficialWeatherLocationCatalog
{
    /**
     * Offset en metros entre el Nivel Medio del Mar (MSL, que es la referencia
     * de Open-Meteo Marine `sea_level_height_msl`) y el Cero Hidrográfico (LAT)
     * en la costa atlántica gaditana (Bonanza / Chipiona / Cádiz).
     */
    public const HYDROGRAPHIC_ZERO_OFFSET_M = 1.90;

    /**
     * Amplitud media en marea viva equinoccial (coeficiente 100) en la costa de
     * Chipiona / Golfo de Cádiz (en metros), usada como referencia cuando se
     * dispone de extremos reales en base de datos.
     */
    public const EQUINOCTIAL_SPRING_RANGE_M = 3.20;

    /**
     * Definición de ciudades/municipios soportados (clave = slug en minúsculas).
     *
     * @var array<string, array{
     *     slug: string,
     *     type: string,
     *     municipality: string,
     *     province: string,
     *     aemet_code: string,
     *     beach_id: int,
     *     station_zone: string,
     *     local_zone: string,
     *     lat: float,
     *     lon: float,
     *     has_corrales: bool
     * }>
     */
    private const CITIES = [
        'chipiona' => [
            'slug' => 'chipiona',
            'type' => 'city',
            'municipality' => 'Chipiona (Cádiz)',
            'province' => 'Cádiz',
            'aemet_code' => '11015',
            'beach_id' => 1101501,
            'station_zone' => 'chipiona_eca',
            'local_zone' => 'Chipiona',
            'lat' => 36.7371,
            'lon' => -6.4348,
            'has_corrales' => true,
        ],
    ];

    /**
     * Definición de provincias soportadas (clave = slug en minúsculas).
     *
     * @var array<string, array{
     *     slug: string,
     *     type: string,
     *     municipality: string,
     *     province: string,
     *     aemet_code: string,
     *     beach_id: int,
     *     station_zone: string,
     *     local_zone: string,
     *     lat: float,
     *     lon: float,
     *     has_corrales: bool
     * }>
     */
    private const PROVINCES = [
        'cadiz' => [
            'slug' => 'cadiz',
            'type' => 'province',
            'municipality' => 'Cádiz',
            'province' => 'Cádiz',
            'aemet_code' => '11012',
            'beach_id' => 1101501,
            'station_zone' => 'chipiona_eca',
            'local_zone' => 'Chipiona',
            'lat' => 36.5271,
            'lon' => -6.2886,
            'has_corrales' => false,
        ],
    ];

    /**
     * Resuelve los metadatos de una ciudad por su slug (insensible a mayúsculas).
     *
     * @return array<string, mixed>|null
     */
    public static function findCity(string $slug): ?array
    {
        $normalized = self::normalizeSlug($slug);

        return self::CITIES[$normalized] ?? null;
    }

    /**
     * Resuelve los metadatos de una provincia por su slug (insensible a mayúsculas y tildes).
     *
     * @return array<string, mixed>|null
     */
    public static function findProvince(string $slug): ?array
    {
        $normalized = self::normalizeSlug($slug);

        return self::PROVINCES[$normalized] ?? null;
    }

    /**
     * Normaliza un slug de entrada quitando espacios y tildes comunes.
     */
    private static function normalizeSlug(string $slug): string
    {
        $lower = mb_strtolower(trim($slug));

        return strtr($lower, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
        ]);
    }
}
