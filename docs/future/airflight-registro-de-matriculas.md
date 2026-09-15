# AirFlight — el registro de matrículas (`bkey`)

> **Resuelto (2026-09-14), con una cuarta opción que no estaba en la tabla de
> abajo:** ni fichero estático, ni tabla propia con importador, ni API
> externa. El receptor (`dump1090-fa`+`skyaware`) ya trae instalada de
> fábrica una base local de matrículas/tipos
> (`/usr/share/skyaware/html/db/`, snapshot de VRS `BasicAircraftLookup`); el
> capturador (`dump1090-to-db`, fuera de este repo) la resuelve por ICAO
> antes de subir, y esta API solo guarda lo que llegue en los nuevos campos
> opcionales `registration`/`aircraft_type` de `POST /aircrafts` (contrato en
> [`docs/info/api/v2/airflight.md`](../info/api/v2/airflight.md)). Sin
> dataset que mantener aquí. El histórico también se rellenó — ver la sección
> de más abajo — pero con un script de un solo uso, no con un comando
> permanente. El análisis de abajo se conserva porque documenta por qué se
> descartaron las otras tres.

## Backfill del histórico — resuelto (2026-09-15), sin comando permanente

Lo resuelto arriba solo llenaba `registration`/`aircraft_type` en subidas
**nuevas**. La propuesta original de esta sección (un comando Artisan
`airflight:backfill_registry`) se descartó por lo mismo que ya se venía
evitando en todo este hilo: es código permanente para un trabajo de un solo
uso. Se hizo así en su lugar, sin dejar nada nuevo en `app/`:

1. Export de los ICAOs con `registration`/`aircraft_type` a `null` desde
   producción (`\copy` a CSV).
2. Ese CSV, contra la copia local de `/usr/share/skyaware/html/db/`
   (7.9 MB, 255 ficheros, traída a un scratchpad local con `rsync`), resuelto
   con un script de Python de usar y tirar que camina el mismo trie por
   prefijo que usa el receptor (algoritmo confirmado contra `dbloader.js` y
   contra ICAOs reales vuestros).
3. Con el resultado, un único `.sql` autocontenido — los datos ya dentro del
   propio fichero como `VALUES (...)`, sin tablas temporales ni `\copy` en el
   servidor — con un `UPDATE ... COALESCE(...)` que solo rellena lo que
   estuviera a `null`, nunca pisa un dato ya bueno.
4. Aplicado en producción el 2026-09-15: **6063 filas actualizadas** (4174
   aviones con matrícula, 6014 con tipo, sobre 8877 totales).

Mismo patrón se usó para `route_last_at` (ver
[`docs/info/airflight.md`](../info/airflight.md)), aunque ese caso no
necesitó ni la Raspberry: se recalculó entero desde `airflight_routes`, que
ya tenía el historial completo — **5175 filas actualizadas**.

Ningún script de estos se ha conservado en el repo: eran de un solo uso, se
borraron del servidor después de aplicarse. Los aviones que sigan sin
matrícula/tipo es porque ese ICAO tampoco está en el snapshot parcial de la
Raspberry (dataset completo real: ~250.000 aeronaves) — se rellenarán solos
si ese avión vuelve a pasar y esta vez sí está.

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

> Creado: 2026-08-30 · Última revisión: 2026-09-15
