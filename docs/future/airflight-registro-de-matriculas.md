# AirFlight — el registro de matrículas (`bkey`)

> Anotado al retirar `GET /airflight/db/{bkey}` en la fase 5. Tu instrucción
> (M6): *«Si no se usa realmente déjalo documentado en "future" tal como se
> planteaba que debería funcionar y ya revisaré si obtengo los más comunes o lo
> soluciono de alguna forma. Por ahora lo anotas y borramos.»*

## Qué era

`GET /api/v2/airflight/db/{bkey}` venía del mapa de dump1090. Su cliente
JavaScript pide, para cada aeronave que aparece, un fichero de la base de
matrículas troceada por prefijo: el `bkey` es el **prefijo hexadecimal del
código ICAO 24-bit** del avión (`db/40.json`, `db/4CA.json`…), no un código de
FlightAware.

Con ese fichero el mapa puede enseñar la **matrícula** (`G-EZBI`) y el **tipo de
aeronave** (`A320`) junto al indicativo de vuelo. Sin él, sólo se ve el
indicativo y el código ICAO en crudo.

## Por qué se ha retirado

El endpoint estaba implementado así:

```php
public function db(string $bkey): JsonResponse
{
    return $this->notFoundResponse('Sin datos de matrícula/tipo para este prefijo ICAO');
}
```

Devolvía 404 siempre, para cualquier prefijo. Un endpoint que sólo sabe decir
«no encontrado» es peor que no tenerlo: parece que existe, el cliente lo llama
una vez por avión, y el resultado es tráfico y latencia a cambio de nada.

## Cómo se haría bien, si algún día interesa

El dataset de dump1090 son unas **250.000 aeronaves**, repartidas en ficheros
JSON por prefijo. Tres formas, de menos a más trabajo:

| Opción | Qué implica | Cuándo compensa |
|---|---|---|
| **Servir los ficheros estáticos** | Descargar el `db/` de dump1090 tal cual a `public/`, y que nginx los sirva. Cero código. Se queda desactualizado. | Es lo más rápido y probablemente suficiente |
| **Tabla propia + importador** | Una tabla `airflight_aircraft_registry` (icao, matrícula, tipo, operador), un comando de importación y un endpoint que consulte por prefijo. Se puede completar con lo que veas pasar de verdad. | Si quieres corregir datos a mano o añadir los aviones que sobrevuelan Chipiona |
| **API externa** | Consultar a un tercero en cada petición. Cuota, latencia y dependencia. | No, salvo que las otras dos fallen |

**Lo que haría yo:** la segunda, pero rellenándola sólo con lo que el receptor
ve de verdad. Tienes años de `airflight_airplanes`: los ICAO que más aparecen
son un puñado comparado con 250.000, y son justo los que importan.

## Estado

- Endpoint retirado en la fase 5.
- El cliente del mapa dejará de pedirlo. Si el JavaScript lo llama, se le quita
  la llamada en la misma ventana.
- No hay nada que migrar: no había datos.
