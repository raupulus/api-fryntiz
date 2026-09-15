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
> dataset que mantener aquí, sin backfill de lo ya guardado — solo lo que se
> suba a partir de ahora. El análisis de abajo se conserva porque documenta
> por qué se descartaron las otras tres.

## Pendiente: backfill del histórico (propuesta, sin implementar)

Lo resuelto arriba solo llena `registration`/`aircraft_type` en subidas
**nuevas**. Los aviones que ya estaban en `airflight_airplanes` antes del
2026-09-14 (miles de filas, ver auditoría de 2026-09) se quedan con esos dos
campos a `null` hasta que el receptor vuelva a verlos — y algunos, los que
pasaron una vez hace tiempo y no vuelven, puede que no se actualicen nunca.

**Propuesta**: un comando Artisan de un solo uso, no una integración
permanente, porque la base de origen (`/usr/share/skyaware/html/db/`, 7.9 MB,
255 ficheros — comprobado en la Raspberry) es un volcado fijo que ya no se
actualiza en origen.

1. **Copiar la base una vez** desde la Raspberry a este servidor (`scp`/`rsync`
   de `pi@172.18.1.58:/usr/share/skyaware/html/db/`) a una ruta fuera de
   control de versiones (p. ej. `storage/app/airflight/registry-snapshot/`,
   añadida a `.gitignore`: son datos de un tercero, no código del proyecto).
   Si el paquete `dump1090-fa`/`piaware` de la Raspberry trae alguna vez una
   base más nueva, se vuelve a copiar y se repite el paso 2.
2. **Comando** `airflight:backfill_registry {--path=} {--force}`, mismo patrón
   que `airflight:remove_duplicate_routes` (dry-run por defecto, cuenta y
   muestra sin escribir; `--force` aplica de verdad):
   - Recorre `AirFlightAirPlane::whereNull('registration')->orWhereNull('aircraft_type')`
     en trozos (`chunkById`), para no cargar de golpe miles de filas.
   - Por cada `icao`, camina el mismo trie por prefijo que ya usa el receptor
     (nivel 1 carácter, baja de nivel si el fichero trae `children` — algoritmo
     confirmado contra `dbloader.js` y contra ICAOs reales vuestros durante el
     análisis de esta nota). Los ficheros JSON de la base (255, 7.9 MB) se
     cargan una vez en memoria al arrancar el comando, no por cada fila.
   - Solo rellena el campo que esté **a `null`** — si `registration` o
     `aircraft_type` ya tienen valor (porque el avión ya volvió a pasar y el
     flujo nuevo lo rellenó), no se toca. Nunca sobrescribe un dato ya bueno
     con el snapshot, aunque discrepen.
   - Informe final: filas procesadas, cuántas ganaron matrícula, cuántas tipo,
     cuántas se quedaron sin nada (el ICAO no está en el snapshot — es del
     dataset completo de 250.000, aquí solo hay una copia parcial/antigua).
3. **No se programa ni se automatiza**: es un `--force` que se ejecuta a mano
   el día que se decida, igual que el resto de comandos de mantenimiento de
   este módulo. Si el snapshot de la Raspberry se refresca más adelante,
   volver a correrlo es seguro (solo toca `null`, es idempotente).

Falta decidir contigo: si quieres que lo implemente así, o prefieres acotarlo
(por ejemplo, solo los aviones vistos en los últimos N meses, en vez de los
~8870 completos).

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

> Creado: 2026-08-30 · Última revisión: 2026-09-14
