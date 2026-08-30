# 🌡️ Observación convencional

**3 endpoints.** Datos **medidos** por estaciones (no predicciones) de las últimas 12 horas.

Requisitos previos: [`00-fundamentos.md`](00-fundamentos.md) · [`ERRATAS.md`](ERRATAS.md)

Leyenda: 🟢 verificado (2026-08-26) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/especificacion/AEMET_OpenData_specification.json` (rutas y `tipomensaje`) · **metadatos del endpoint de observación** (el diccionario de 39 campos con sus unidades) · **verificación en vivo del 2026-08-26** (estructura, tamaños, formato del `gzip`).

---

## Resumen

| Endpoint | Estado | Formato real |
|---|---|---|
| `GET /api/observacion/convencional/datos/estacion/{idema}` | 🟢 | JSON `list[N]` |
| `GET /api/observacion/convencional/todas` | 🟢 | JSON, **3,6 MB** |
| `GET /api/observacion/convencional/mensajes/tipomensaje/{tipomensaje}` | 🟢 | **`gzip` real, 6,5 MB** |

---

## Observación de una estación 🟢

```
GET /api/observacion/convencional/datos/estacion/{idema}
```

| | |
|---|---|
| `{idema}` | Indicativo climatológico. Ej.: `1495` (Vigo/Peinador) |
| Cobertura 🔵 | Últimas **12 horas**, con un registro por hora |
| Registros obtenidos 🟢 | 13 (una lista por hora) |
| Tamaño 🟢 | ~5,4 KB |
| Periodicidad 🔵 | "continuamente" |
| RSS 🔵 | Sí — canal "Observación convencional horaria" |
| TTL 🟡 | 30–60 min |

Verificado con `1495`.

> ⚠️ El spec escribe el parámetro como "**Í**ndicativo climatológico" (tilde incorrecta) solo en
> este endpoint ([`ERRATAS.md` C11](ERRATAS.md#c11-erratas-menores-de-redacción)).

### Estructura 🟢

Raíz: `list` con **un objeto plano por hora**. No hay anidamiento.

```json
[{
  "idema": "1495",
  "ubi": "VIGO/PEINADOR",
  "lat": 42.238616,
  "lon": -8.623765,
  "alt": 254.6,
  "fint": "2026-08-25T19:00:00+0000",
  "prec": 0.0,
  "pres": 983.6,
  "hr": 76.0,
  "vv": 2.6,
  "vmax": 6.7,
  "dv": 250.0,
  "dmax": 270.0,
  "stdvv": 0.2
}]
```

### Campos: 39 en total, solo 5 obligatorios 🟢

Diccionario oficial tomado de la URL `metadatos` del endpoint (`campos[]`), no inferido.
Las unidades vienen en la propia `descripcion` de AEMET.

#### Obligatorios (`requerido: true`) — los únicos garantizados

| Campo | Tipo | Significado |
|---|---|---|
| `idema` | string | Indicativo climatológico de la estación meteorológica automática |
| `lon` | float | Longitud de la estación (grados) |
| `lat` | float | Latitud de la estación (grados) |
| `alt` | float | Altitud de la estación (metros) |
| `ubi` | string | Ubicación / nombre de la estación |

#### Opcionales — 34 campos que pueden no venir

| Campo | Tipo | Significado y unidad |
|---|---|---|
| `fint` | string | Fecha-hora **final** del periodo de observación. ⚠️ **Los datos son de la hora ANTERIOR** a la indicada (hora UTC) |
| `ta` | float | Temperatura instantánea del aire (°C) |
| `tamin` / `tamax` | float | Mínima / máxima de los 60 valores instantáneos de la hora (°C) |
| `tpr` | float | Temperatura del punto de rocío (°C) |
| `ts` | float | Temperatura junto al suelo (°C) |
| `tss5cm` / `tss20cm` | float | Temperatura del subsuelo a 5 / 20 cm (°C) |
| `hr` | float | Humedad relativa instantánea (%) |
| `pres` | float | Presión en el nivel del barómetro (hPa) |
| `pres_nmar` | float | Presión reducida al nivel del mar (solo estaciones a ≤750 m) (hPa) |
| `prec` | float | Precipitación acumulada 60 min, por **pluviómetro** (mm = l/m²) |
| `pacutp` | float | Precipitación acumulada 60 min, por **disdrómetro** (mm) |
| `pliqtp` / `psolt` | float | Precipitación líquida / sólida acumulada 60 min (mm) |
| `vv` | float | Velocidad media del viento, media escalar (m/s) |
| `vmax` | float | Velocidad máxima: máximo mantenido 3 s en los 60 min (m/s) |
| `dv` | float | Dirección media del viento, 10 min anteriores (grados) |
| `dmax` | float | Dirección del viento máximo de los 60 min (grados) |
| `vvu` `vmaxu` `dvu` `dmaxu` | float | Los cuatro anteriores medidos por **sensor ultrasónico** |
| `stdvv` / `stddv` | float | Desviación estándar de velocidad / dirección, 10 min |
| `stdvvu` / `stddvu` | float | Ídem con sensor ultrasónico |
| `rviento` | float | Recorrido del viento en 60 min (**Hm**, hectómetros) |
| `inso` | float | Duración de la insolación en los 60 min |
| `vis` | float | Visibilidad, promedio de 10 min |
| `nieve` | float | Espesor de la capa de nieve, 10 min (cm) |
| `geo700` `geo850` `geo925` | float | Altura de las superficies barométricas de 700 / 850 / 925 hPa |

> [!IMPORTANT]
> **De 39 campos, 34 son opcionales.** Comprobado: la estación `1495` (Vigo/Peinador) devolvió 14
> campos y **no incluía `ta`** — la temperatura del aire, probablemente el dato que más se querría.
> **No des ningún campo opcional por garantizado**, ni siquiera los básicos.

> ⚠️ **`fint` es la hora FINAL del periodo, y los datos son de la hora anterior.** Un registro con
> `fint = 19:00` describe de 18:00 a 19:00. Etiquetarlo como "las 19:00" desplaza toda la serie.

> 🟢 **Hay pares de sensores**: los campos con sufijo `u` son de sensor ultrasónico y conviven con
> los mecánicos. No son duplicados: son instrumentos distintos. Elige uno y sé consistente.

### Detalles que importan 🟢

- **A diferencia del resto de la API, aquí los números son números** (`float`), no cadenas.
- **`fint` usa el desplazamiento `+0000`**, no `Z` ni `+00:00`. Los parseadores estrictos de
  ISO 8601 pueden rechazarlo.
- ⚠️ **Este payload *sí* decodifica como UTF-8**, pero solo porque su contenido es numérico y sin
  acentos. **No es UTF-8**: es la trampa descrita en
  [`ERRATAS.md` A2](ERRATAS.md#a2-trampa-derivada-algunos-endpoints-parecen-utf-8-). Convertir la
  codificación igual que en el resto.
- **Los campos varían según la estación** 🟢: solo 5 de 39 son obligatorios (ver arriba).
- `alt: 254.6` para Vigo/Peinador es la altitud **de la estación** (el aeropuerto), no la del
  municipio (que el maestro da como 19 m). No confundirlas.

### Cómo obtener un `idema` 🟡

No hay un maestro de estaciones de observación. Opciones:

1. `GET /api/valores/climatologicos/inventarioestaciones/todasestaciones` — inventario de estaciones
   climatológicas ([`06-climatologia.md`](06-climatologia.md)). 🟡 No necesariamente coincide con las
   de observación.
2. `GET /api/observacion/convencional/todas` y ver qué estaciones aparecen (🔴 volumen sin medir).
3. `GET /api/productos/climatologicos/capasshape/{tipoestacion}` — capas SHAPE geolocalizadas.

⚠️ 🔵 Solo se publican estaciones **validadas**: que una estación exista no implica que esté en la
API ([`LIMITACIONES.md`](LIMITACIONES.md#retrasos-y-disponibilidad-de-datos)).

### Múltiples estaciones 🔴

El spec **no** menciona multi-valor en este endpoint, pero sí en el de climatologías diarias, y la
FAQ 4.8 da el ejemplo `estacion/8178D,8050X`.
🔴 **Sin verificar aquí.** Comprobar antes de usarlo
([`ERRATAS.md` D1](ERRATAS.md#d1-varios-endpoints-aceptan-múltiples-valores-separados-por-comas-)).

---

## Observación de todas las estaciones 🟢

```
GET /api/observacion/convencional/todas
```

🔵 Últimas 12 h de **todas** las estaciones con datos en ese periodo.

🟢 Verificado: **3,6 MB** (`Content-Length` informado, así que se puede decidir antes de descargar).
Misma estructura que el endpoint por estación: `list` de objetos planos, empezando por
`{"idema": "0002I", "lon": 0.871385, "fint": "2026-08-25T20:00:00+0000", …}`.

🟡 Con pocas ubicaciones, N peticiones individuales sale mucho mejor. Pero recuerda que **el cubo de
cuota es por endpoint** (40 peticiones): a partir de ~40 estaciones, este agregado sale más barato en
cuota, aunque cueste 3,6 MB. Ver
[`LIMITACIONES.md`](LIMITACIONES.md#el-cubo-es-por-plantilla-de-endpoint-no-global-).

---

## Mensajes de observación 🟢

```
GET /api/observacion/convencional/mensajes/tipomensaje/{tipomensaje}
```

🔵 Boletines meteorológicos en crudo, en formato estándar OMM.

| `{tipomensaje}` | Contenido 🔵 | Ventana 🔵 |
|---|---|---|
| `synop` | Observación de superficie | Últimas 24 h |
| `temp` | Radiosondeos (perfil vertical) | Últimas 24 h |
| `climat` | Resúmenes climáticos mensuales | Últimos 40 días |

🔵 Según el spec: *"El resultado de la petición es un fichero en formato **tar.gz**, que contiene los
boletines en formato json y bufr"*.

🟢 Y los metadatos lo confirman con sus propias palabras:
`formato: "tar comprimido que contiene ficheros en BUFR y en JSON"` · `periodicidad: **Horaria**`.
Sus `campos` van a 0: no hay diccionario para este producto.

> [!NOTE]
> 🟢 **Verificado con `synop`: aquí sí es `gzip` de verdad** (magic `1f8b`), a diferencia de los
> avisos CAP, que son `tar` plano. Y pesa **6,5 MB** — el producto más grande de la API.
> Su `Content-Type` es `application/octet-stream;charset=ISO-8859-15`, que **no** permite
> distinguirlo del `x-gtar` sin comprimir de los avisos.
> **Comprueba el magic (`1f8b`) antes de descomprimir**, no el `Content-Type`.
> Ver [`ERRATAS.md` B2-bis](ERRATAS.md#b2-bis-tar-sin-comprimir-en-avisos-gzip-de-verdad-en-mensajes-).

🟡 **BUFR es un formato binario de la OMM** que requiere librerías especializadas. Los `.json` del
mismo paquete son la vía práctica. 🔴 Sin verificar su estructura.

RSS 🔵: cuatro canales — `climat`, `synop`, `temp` y "todos".

🟡 Producto de nicho: para uso general, el endpoint de observación por estación da lo mismo ya
estructurado.

---

## Pendiente de verificar

**Cobertura: 3 de 3.**

| # | Qué | Prioridad |
|---|---|---|
| 1 | ✅ *Resuelto*: diccionario de 39 campos volcado desde los metadatos | — |
| 2 | ✅ *Resuelto*: 34 de 39 son opcionales | — |
| 3 | Contenido interno del `gzip` de mensajes | **Descartado** — producto prescindible, ver arriba |
| 4 | Si el multi-`idema` funciona aquí (sí funciona en inventario de estaciones) | Media |
| 5 | Si el inventario climatológico sirve para obtener `idema` de observación | Media |

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
