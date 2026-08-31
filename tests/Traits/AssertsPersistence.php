<?php

declare(strict_types=1);

namespace Tests\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Comprobaciones de PERSISTENCIA: que lo que el endpoint acepta llega de verdad
 * a la base de datos.
 *
 * Existe por el hallazgo N279 de la auditoría: la suite pasaba entera
 * (115 tests, 575 assertions) conviviendo con ~280 bugs, porque comprobaba
 * códigos HTTP y la FORMA del JSON, casi nunca el dato guardado.
 *
 * De las 575 assertions que había, sólo 3 miraban la base de datos.
 *
 * El valor de este trait está en el MENSAJE de error: no dice "falló", dice
 * exactamente qué campo se perdió y por cuál de las tres causas posibles.
 */
trait AssertsPersistence
{
    /**
     * Comprueba que cada campo enviado en $payload está guardado en $row.
     *
     * Distingue las tres causas por las que un campo se pierde, que es lo que
     * convierte el fallo en accionable:
     *
     *   1. La columna NO EXISTE            -> el FormRequest valida algo inventado
     *   2. Existe pero llegó NULL          -> el $fillable del modelo la descarta
     *   3. Existe con OTRO valor           -> algún casteo o mutador la transforma
     *
     * @param  Model  $row  La fila recién guardada, ya refrescada desde la base de datos.
     * @param  array<string,mixed>  $payload  Lo que se envió al endpoint.
     * @param  array<int,string>  $except  Campos que legítimamente no se guardan tal cual.
     */
    protected function assertPersisted(Model $row, array $payload, array $except = []): void
    {
        $table = $row->getTable();
        $missing = [];

        foreach ($payload as $field => $expected) {
            if (in_array($field, $except, true)) {
                continue;
            }

            if (! Schema::hasColumn($table, $field)) {
                $missing[] = sprintf(
                    '%-22s LA COLUMNA NO EXISTE en %s -> el FormRequest valida un campo inventado',
                    $field, $table
                );

                continue;
            }

            $real = $row->getAttribute($field);

            if ($real === null && $expected !== null) {
                $missing[] = sprintf(
                    '%-22s la columna existe y llegó NULL -> ¿está en el $fillable de %s?',
                    $field, $row::class
                );

                continue;
            }

            if (! $this->sameValue($expected, $real)) {
                $missing[] = sprintf(
                    '%-22s se guardó CAMBIADO: se envió %s y hay %s',
                    $field, $this->asText($expected), $this->asText($real)
                );
            }
        }

        $this->assertSame([], $missing, sprintf(
            "\n%d de %d campos enviados a la API NO llegaron a `%s`:\n\n  %s\n",
            count($missing), count($payload) - count($except), $table,
            implode("\n  ", $missing)
        ));
    }

    /**
     * Comprueba que un campo NO se guarda: para dejar por escrito lo que la API
     * acepta y tira a propósito, y que nadie lo "arregle" sin querer.
     */
    protected function assertNotPersisted(Model $row, string $field, string $porque): void
    {
        $real = Schema::hasColumn($row->getTable(), $field)
            ? $row->getAttribute($field)
            : null;

        $this->assertNull($real, "Se esperaba que `{$field}` NO se guardara ({$porque}) y sí se guardó: ".$this->asText($real));
    }

    /**
     * Compara con la tolerancia justa: los flotantes de los sensores pasan por
     * PostgreSQL y vuelven como string o con menos precisión.
     */
    private function sameValue(mixed $expected, mixed $real): bool
    {
        if (is_bool($expected)) {
            return (bool) $real === $expected;
        }

        if (is_int($expected) || is_float($expected)) {
            return is_numeric($real) && abs(((float) $real) - ((float) $expected)) < 0.0001;
        }

        if (is_array($expected)) {
            return $expected === (is_string($real) ? json_decode($real, true) : $real);
        }

        // Fechas: comparar el instante, no la cadena.
        if (is_string($expected) && strtotime($expected) !== false && ! is_numeric($expected)) {
            $realTexto = $real instanceof \DateTimeInterface ? $real->format('Y-m-d H:i:s') : (string) $real;

            if (strtotime($realTexto) !== false) {
                return abs(strtotime($realTexto) - strtotime($expected)) <= 1;
            }
        }

        return (string) $real === (string) $expected;
    }

    private function asText(mixed $value): string
    {
        return match (true) {
            is_null($value) => 'NULL',
            is_bool($value) => $value ? 'true' : 'false',
            is_array($value) => json_encode($value),
            $value instanceof \DateTimeInterface => $value->format('Y-m-d H:i:s'),
            default => '`'.$value.'`',
        };
    }
}
