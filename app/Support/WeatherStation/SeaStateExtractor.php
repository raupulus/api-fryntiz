<?php

declare(strict_types=1);

namespace App\Support\WeatherStation;

/**
 * Saca el estado de la mar (escala Douglas) del texto libre que trae la
 * predicción marítima costera de AEMET (`AEMETCoast::subzone_text`).
 *
 * AEMET no expone el estado de la mar como campo estructurado: viene mezclado
 * en prosa junto al viento y la visibilidad (ver `docs/apis/aemet/07-maritima.md`),
 * ej. «Componente W 3 o 4… Marejadilla o marejada. Mar de fondo del W…». Se
 * busca la primera mención de la escala oficial en el texto: es la que AEMET
 * redacta primero porque es la vigente al principio del periodo de validez.
 */
class SeaStateExtractor
{
    /**
     * Escala Douglas en español, tal cual la usa AEMET. El orden importa:
     * los términos compuestos van antes que su raíz («fuerte marejada» antes
     * que «marejada», «muy gruesa» antes que «gruesa») para no cortar la
     * coincidencia a mitad de frase.
     *
     * @var array<int,string>
     */
    private const SCALE = [
        'Fuerte marejada',
        'Muy gruesa',
        'Marejadilla',
        'Marejada',
        'Gruesa',
        'Arbolada',
        'Montañosa',
        'Enorme',
        'Rizada',
        'Calma',
    ];

    /**
     * Primer término de la escala que aparece en el texto, o `null` si no
     * hay ninguno (texto vacío, o formato que no se reconoce).
     */
    public static function extract(?string $text): ?string
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        $bestTerm = null;
        $bestPosition = null;

        foreach (self::SCALE as $term) {
            $position = mb_stripos($text, $term);

            if ($position === false) {
                continue;
            }

            if ($bestPosition === null || $position < $bestPosition) {
                $bestPosition = $position;
                $bestTerm = $term;
            }
        }

        return $bestTerm;
    }
}
