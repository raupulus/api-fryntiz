# Tests de persistencia — la red de la fase 1

> **Estos tests DEBEN FALLAR hoy.** Cada fallo es un bug ya fichado en la auditoría.
> Cuando pasen todos, la fase 2 está terminada.

## Por qué existen

La suite anterior pasaba entera —**115 tests, 575 assertions**— conviviendo con **280
hallazgos**. De esas 575 assertions, **3** comprobaban que una fila quedase guardada
(**N279**).

Lo que sí comprobaba, y bien: que una petición **inválida** se rechaza (86 assertions de
401 y 422). Lo que no comprobaba nunca: que una petición **válida** se guarde.

Por eso **19 de los 21 endpoints de escritura IoT** no tenían ningún test de camino
feliz, y por eso este patrón sobrevivió años:

> **La API responde 201 y el dato no llega.**

## Qué hacen distinto

`Tests\Traits\AssertsPersistence` compara el payload enviado con la fila guardada y,
cuando algo se pierde, dice **cuál de las tres causas** es:

```
7 de 17 campos enviados a la API NO llegaron a `solar_charges`:

  load_amperage          LA COLUMNA NO EXISTE en solar_charges -> el FormRequest valida un campo inventado
  battery_percentage     la columna existe y llegó NULL -> ¿está en el $fillable de App\Models\Hardware\SolarCharge?
  temperature            la columna existe y llegó NULL -> ¿está en el $fillable de App\Models\Hardware\SolarCharge?
  ...
```

Eso convierte «algo falla» en «este campo, por esta razón».

## Qué cubre cada fichero

| Fichero | Endpoint | Qué debería destapar |
|---|---|---|
| `SolarChargePersistenceTest` | `POST /hardware/solar-charge` | **7 de 17 campos del Renogy Rover se pierden** (H2, R-4): 6 los descarta el `$fillable` y `load_amperage` no es columna (es `load_current`) |
| `EnergyMonitorPersistenceTest` | `POST /hardware/energy` | **El endpoint no guarda ningún dato de energía**: escribe en `hardware_energy`, que es la tabla de *configuración*, no de lecturas (R-3, N140) |
| `KeyCounterPersistenceTest` | `POST /keycounter/keyboard` y `/mouse` | La sesión completa; que `MouseResource` no devuelva `score` (R-6); que un campo ausente no acabe en `0` por el `(int) null` |
| `WeatherStationPersistenceTest` | Los 12 `POST /weatherstation/*/store` | Que cada sensor llegue a su tabla; que un `0` real (lluvia, noche) no se guarde como `NULL`; que el lote del genérico no pierda sensores |
| `SmartPlantPersistenceTest` | `POST /smartplant/register` | Que la lectura quede ligada a la planta (**H5**) y que un sensor ausente deje `NULL`, no `0`. Además, que `user_id` no se evapore (**N288**) |
| `AirFlightPersistenceTest` | `POST /airflight/register` y `/register/batch` | Que la posición del avión no se tire a la basura (**N291**), que el mismo avión no se duplique en cada sondeo (**N292**) y que quede constancia del receptor (**N293**) |

Y fuera de esta carpeta, en `tests/Unit/Resources/ResourceValuesTest.php`: los
Resources que devuelven claves siempre nulas (**N280**). No pasa por HTTP — inserta la fila
con `DB::table()` y le pasa el Resource por encima, así el único culpable posible de un
`null` es el Resource.

## Cómo se leen los fallos

| El mensaje dice | Significa | Se arregla en |
|---|---|---|
| `LA COLUMNA NO EXISTE` | El FormRequest valida un campo inventado, o el nombre no coincide con la columna | Fase 2.1 |
| `llegó NULL` | La columna existe y el `$fillable` del modelo la descarta | Fase 2.1 |
| `se guardó CAMBIADO` | Un cast o un mutador la transforma por el camino | Fase 2.8 |

## Lo que falta por cubrir

- [x] `POST /airflight/register` y `/register/batch`
- [x] Los Resources rotos (**N280**) — en `tests/Unit/Resources/`
- [ ] `PUT /hardware/device-status` — **ya tiene test bueno** (`store_device_status_updates_last_known_state`), que es de donde sale la plantilla
- [ ] `POST /contact-messages` y la newsletter (fase 5, cambian de forma)
- [ ] `tests/Unit/Policies/` y `tests/Unit/Services/`, hoy vacíos

## Ojo con el data provider de la estación

Sólo cinco sensores usan el campo `value` (los de `StoreSensorRequest`: temperature,
humidity, pressure, eco2, tvoc). Los otros seis tienen FormRequest propio con sus propios
campos. Mandar `value` a `/light/store` no da 422: da **201 con la fila vacía**, o **500**
si la columna es NOT NULL. Ese fue el primer tropiezo al escribir estos tests, y de ahí
salió **N286**.

## Y una advertencia

Estos tests usan `authenticatedHeaders()`, que emite el token con
`createToken('test-token')` **sin abilities**, o sea `['*']`. Por eso pasan los middleware
`ability:*`. Cuando se acoten las abilities (fase 3), **habrá que emitir aquí tokens con
la ability justa**, o los tests dejarán de probar lo que creen probar.
