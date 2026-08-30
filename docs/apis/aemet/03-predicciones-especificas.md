# 🏔️ Predicciones específicas: montaña, nivológica, playa y UVI

**5 endpoints.** Productos de predicción para entornos concretos.

> Las predicciones por municipio, que también pertenecen al tag `predicciones-especificas` de la
> especificación, están separadas en [`01-predicciones-municipios.md`](01-predicciones-municipios.md)
> por ser el grupo de uso más habitual.

Requisitos previos: [`00-fundamentos.md`](00-fundamentos.md) · [`ERRATAS.md`](ERRATAS.md)

Leyenda: 🟢 verificado (2026-08-26) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/especificacion/AEMET_OpenData_specification.json` (rutas y catálogos de área y día) · **metadatos de los 4 endpoints verificados** · `src/catalogos/Playas_codigos.csv` (las 590 playas) · **verificación en vivo del 2026-08-26** · y el **vocabulario de descripciones de playa** procedente de 2.067 registros de una integración de terceros (ver [`DOCUMENTACION-TERCEROS.md`](DOCUMENTACION-TERCEROS.md)).

---

## Resumen

| Endpoint | Estado | Formato real |
|---|---|---|
| `GET /api/prediccion/especifica/playa/{playa}` | 🟢 | JSON `list[1]` |
| `GET /api/prediccion/especifica/uvi/{dia}` | 🟢 | JSON **`dict[4]`**, 5,5 KB |
| `GET /api/prediccion/especifica/montaña/pasada/area/{area}` | 🟢 | JSON `list[1]`, 2,0 KB |
| `GET /api/prediccion/especifica/montaña/pasada/area/{area}/dia/{dia}` | 🔴 | 🟡 previsiblemente igual |
| `GET /api/prediccion/especifica/nivologica/{area}` | 🟢 | **Texto plano**, 588 B |

---

## Predicción de playa 🟢

```
GET /api/prediccion/especifica/playa/{playa}
```

| | |
|---|---|
| `{playa}` | Código de 7 dígitos: 5 del municipio INE + 2 de orden. Ej.: `3605706` (Samil, Vigo) |
| Catálogo | `src/catalogos/Playas_codigos.csv` — **590 playas** 🟢. ISO-8859, separador `;`, CRLF |
| Tamaño 🟢 | ~3,8 KB |
| Cobertura 🟢 | 3 días (`prediccion.dia[3]`) |
| RSS 🔵 | Sí — canal "Predicción playa" |
| TTL 🟡 | 6 h |

Verificado con `3605706`.

### Estructura 🟢

```
[0]
├── origen: { productor, web, language, copyright, notaLegal }
├── elaborado: "2026-08-26T05:50:17"
├── nombre: "Samil"
├── localidad: -29479          ← ⚠️ NEGATIVO, ver abajo
├── id: 3605706
└── prediccion.dia[3]
    ├── fecha: 20260826        ← ⚠️ ENTERO AAAAMMDD, no ISO
    ├── estadoCielo: { value: "", f1: -116, descripcion1: "muy nuboso con lluvia", f2: 140, descripcion2: "…" }
    ├── viento:      { value: "", f1: 220,  descripcion1: "moderado", f2: 220, descripcion2: "moderado" }
    ├── oleaje:      { value: "", f1: 310,  descripcion1: "débil",    f2: 310, descripcion2: "débil" }
    ├── tMaxima:     { value: "", valor1: 20 }      ← °C, real
    ├── tAgua:       { value: "", valor1: 19 }      ← °C, real
    ├── uvMax:       { value: "", valor1: 6 }       ← índice UV, real
    ├── sTermica:    { value: "", valor1: 450, descripcion1: "suave" }   ← ⚠️ 450 NO son grados
    ├── tagua:       { … }      ← ⚠️ DUPLICADO de tAgua en minúscula
    ├── stermica:    { … }      ← ⚠️ DUPLICADO de sTermica
    └── tmaxima:     { … }      ← ⚠️ DUPLICADO de tMaxima
```

### ⚠️ Trampas de este payload 🟢

Todas verificadas en la respuesta real. **Ninguna está documentada por AEMET.**

| Trampa | Detalle |
|---|---|
| **Campos duplicados en dos grafías** | `tAgua`/`tagua`, `sTermica`/`stermica`, `tMaxima`/`tmaxima` coexisten con el mismo contenido. Elige una grafía y sé consistente; 🔴 sin verificar si alguna vez difieren |
| **`f1`/`f2` son códigos, no magnitudes** | `estadoCielo.f1 = -116`, `viento.f1 = 220`, `oleaje.f1 = 310`. Usa siempre `descripcion1`/`descripcion2`, nunca los números |
| **`sTermica.valor1 = 450`** | No son 450 °C. Es un código cuya lectura es `descripcion1: "suave"`. ❌ **Sin tabla de códigos: los metadatos de playa no documentan el bloque `prediccion`** |
| **`localidad = -29479`** | Negativo y sin sentido. 🟢 Los metadatos lo definen como *"Indicativo del municipio al que pertenece la playa"*, que para Samil sería `36057`: **el dato es defectuoso, no la interpretación**. Usa `id` |
| **`value` siempre vacío** | Todos los objetos traen `"value": ""`. Ignorarlo |
| **`fecha` es un entero `AAAAMMDD`** | `20260826`, no la cadena ISO 8601 que usan los productos de municipio. **Formato de fecha inconsistente entre productos de la misma API** |
| **`f1`/`f2` = dos momentos del día** | 🟡 Inferido: probablemente mañana y tarde. Sin confirmar |

Los únicos campos con magnitudes físicas fiables son `tMaxima`, `tAgua` y `uvMax` 🟢.

### ⚠️ Los metadatos de playa están incompletos 🟢

`formato: json/xml` · `periodicidad: Dos veces al día` · pero **solo declaran 6 campos**:
`id`, `elaborado`, `nombre`, `localidad` y **dos entradas vacías** (`id: null`).

**No documentan nada del bloque `prediccion`**, así que los códigos `f1`/`f2` y `sTermica.valor1`
**no tienen fuente oficial**. Lo único disponible es el `descripcion1`/`descripcion2` que viene en el
propio payload — que es, de hecho, lo que hay que usar.

### El vocabulario de descripciones de playa 🟡

Los códigos `f1`/`f2` crudos son **identificadores internos opacos** (`-116`, `-126`, `220`, `310`,
`450`) sin aritmética deducible y **sin fuente oficial** (los metadatos no documentan el bloque
`prediccion`). Pero el **conjunto de descripciones sí es cerrado**, y eso es lo utilizable.

Extraído de **2.067 registros históricos reales** de una integración en producción
(`meteorology_aemet_prediction_beachs`, ~4.000 observaciones por variable):

| Variable | Valores posibles de `descripcion` |
|---|---|
| **Cielo** | `Despejado` · `Nuboso` · `Muy nuboso` · `Chubascos` · `Muy nuboso con lluvia` |
| **Viento** | `Flojo` · `Moderado` · `Fuerte` |
| **Oleaje** | `Débil` · `Moderado` · `Fuerte` |
| **Sensación térmica** | `Muy Fresco` · `Fresco` · `Suave` · `Calor Agradable` · `Calor Moderado` · `Calor Fuerte` |

🟡 **Úsalo como enum de destino**, mapeando por la cadena `descripcion1`/`descripcion2`, nunca por
`f1`/`f2`. La escala de sensación térmica es ordinal de 6 niveles (de más frío a más calor), útil para
colorear.

⚠️ Es una muestra de una integración de terceros, no un catálogo oficial: **pueden existir más
valores** que no aparecieron en ese periodo. Prevé un caso por defecto.

⚠️ Ojo: el vocabulario de cielo de playa (**5 valores**) **no es el mismo** que el de
[`estadoCielo`](13-iconos-estado-cielo.md) de las predicciones municipales (**35 códigos**). Son dos
sistemas distintos y no se pueden mezclar.

### Códigos de playa 🟢

`src/catalogos/Playas_codigos.csv`:

```
ID_PLAYA;NOMBRE_PLAYA;ID_PROVINCIA;NOMBRE_PROVINCIA;ID_MUNICIPIO;NOMBRE_MUNICIPIO;LATITUD;LONGITUD
3605706;Samil;36;Pontevedra;36057;Vigo;42º 13' 12";-08º 46' 20"
```

Las coordenadas vienen como **texto en grados/minutos/segundos**, no en decimal.

---

## Predicción de radiación ultravioleta (UVI) 🟢

```
GET /api/prediccion/especifica/uvi/{dia}
```

| `{dia}` | Significado |
|---|---|
| `0` | Día actual |
| `1`–`4` | Hasta 4 días vista |

⚠️ `dia` aquí va de `0` a `4`. En otros endpoints el mismo nombre usa otras escalas
([`ERRATAS.md` C4](ERRATAS.md#c4-dia-reutiliza-el-nombre-para-4-escalas-distintas-)).

RSS 🔵: sí, canal "Predicción de radiación ultravioleta (UVI)".

### Estructura 🟢

Verificado con `dia=0`: **5,5 KB, y la raíz es un `dict`, no una `list`** — es uno de los dos únicos
productos así en toda la API. Y **sus claves van en MAYÚSCULAS**, única convención de ese estilo:

```
FECHA_ELABORACION, FECHA_MOD, FECHA_VALIDEZ, CIUDAD
```

Periodicidad de los metadatos: **"Una vez al día"**. TTL 🟡: 12 h.

⚠️ **Los metadatos de UVI también están incompletos** 🟢: declaran 4 campos (`version`,
`FECHA_ELABORACION`, `FECHA_VALIDEZ` y una entrada vacía) y **omiten `FECHA_MOD` y `CIUDAD`**, que sí
vienen en el payload. La descripción oficial del producto es *"Predicción de Índice de radiación UV
máximo en condiciones de cielo despejado"*, y `FECHA_VALIDEZ` cubre **D+0 a D+3**.

⚠️ Código que haga `$data[0]` **falla aquí**. Ver
[`ERRATAS.md` E12](ERRATAS.md#e12-raíz-dict-en-vez-de-list-en-algunos-productos-) y
[E13](ERRATAS.md#e13-uvi-usa-claves-en-mayúsculas-).

✅ **Cobertura resuelta** 🟢: `CIUDAD` es una **lista de 59 ciudades** (las capitales de provincia e
insulares), cada una con su valor de índice UV:

```json
"CIUDAD": [{"id":"02003","valor":"Albacete","uv":"8","canarias":"0"},
           {"id":"03014","valor":"Alacant/Alicante","uv":"7","canarias":"0"}, …]
```

| Campo | Significado |
|---|---|
| `id` | **Código INE de municipio** (5 dígitos) — cruza directamente con el maestro |
| `valor` | Nombre de la ciudad |
| `uv` | Índice UV máximo previsto |
| `canarias` | Bandera `0`/`1`, 🟡 presumiblemente para distinguir el huso horario canario |

⚠️ **Es un producto de 59 puntos, no nacional ni por municipio arbitrario.** Si la localidad que
necesitas no es capital, no está: habría que usar la más cercana.

---

## Predicción de montaña 🟢 (variante sin día)

```
GET /api/prediccion/especifica/montaña/pasada/area/{area}
GET /api/prediccion/especifica/montaña/pasada/area/{area}/dia/{dia}
```

> [!IMPORTANT]
> **La ruta contiene `ñ`.** 🟢 Resuelto el 2026-08-26:
>
> | Codificación | Resultado |
> |---|---|
> | `monta%C3%B1a` — UTF-8 **NFC** (`ñ` = U+00F1) | ✅ **200** |
> | `montan%CC%83a` — UTF-8 **NFD** (`n` + tilde combinante) | ❌ **404** |
>
> Hay que **percent-encodear UTF-8 en forma NFC**. Ojo si el valor viene de macOS, que normaliza a
> NFD por defecto: **normaliza a NFC antes de construir la URL**.
> Ver [`ERRATAS.md` D11](ERRATAS.md#d11-la-ruta-con-ñ-exige-utf-8-percent-encoded-en-forma-nfc-).

### Estructura 🟢

Verificado con `peu1` (Picos de Europa): `list[1]`, 2,0 KB, con claves
`origen`, `seccion`, `id`, `nombre`.

✅ **`seccion` resuelto** 🟢: es una lista de objetos con `apartado`, `lugar` y **`parrafo[]`**:

```json
"seccion": [{ "apartado": [], "lugar": [],
  "parrafo": [
    {"numero":"1","texto":"(En las 24 horas previas a las 10:00 hora oficial del 25 de agosto de 2026)"},
    {"numero":"2","texto":""},
    {"numero":"3","texto":"Nuboso o muy nuboso, con periodos de visibilidad reducida. Precipitaciones…"},
    {"numero":"5","texto":"TEMPERATURAS MÍNIMAS:"},
    {"numero":"6","texto":"7 ºC en Cabaña Verónica (2239 m), 9 ºC en Mirador del Cable (1910 m)…"}
  ]}]
```

⚠️ Detalles que importan:
- **Los párrafos con `texto: ""` son separadores**, no errores. Hay que conservarlos o el texto se
  apelmaza.
- Algunos párrafos son **encabezados en mayúsculas** (`TEMPERATURAS MÍNIMAS:`): el contenido es prosa
  con estructura implícita, no campos.
- `apartado` y `lugar` venían **vacíos** en la muestra. 🔴 Sin saber cuándo se rellenan.
- El primer párrafo suele traer **la ventana temporal** entre paréntesis: útil para validar frescura
  además de `validez_ini`/`validez_fin`.

`formato: json/xml` · `periodicidad: Una vez al día` 🟢

Los metadatos documentan 4 campos útiles (los otros 3 van vacíos):

| Campo | Significado |
|---|---|
| `id` | Indicativo del área de montaña |
| `nombre` | 🟢 **"Tiempo pasado"** — confirma que este endpoint es de tiempo pasado, pese al título del spec |
| `elaborado` | Fecha de elaboración |
| `validez_ini` / `validez_fin` | **Ventana de validez del boletín** — útil para descartar contenido caducado |

> [!NOTE]
> ⚠️ **Nombres contradictorios.** El primero es "Tiempo pasado" (resumen de las últimas 24-36 h) 🔵.
> El segundo se titula **"Tiempo actual"** pese a llevar `/pasada/` en la ruta y aceptar días
> futuros. El `/pasada/` parece un resto histórico.
> Ver [`ERRATAS.md` C8](ERRATAS.md#c8-endpoint-con-nombre-contradictorio-).

`{area}` — 9 áreas montañosas:

| Código | Área |
|---|---|
| `peu1` | Picos de Europa |
| `nav1` | Pirineo Navarro |
| `arn1` | Pirineo Aragonés |
| `cat1` | Pirineo Catalán |
| `rio1` | Ibérica Riojana |
| `arn2` | Ibérica Aragonesa |
| `mad2` | Sierras de Guadarrama y Somosierra |
| `gre1` | Sierra de Gredos |
| `nev1` | Sierra Nevada |

`{dia}`: `0` (hoy) a `3`. ⚠️ El spec escribe "d+3 (**siguente** a pasado mañana)".

RSS 🔵: varios canales — "todos", "actual", "pasado" y **uno por área montañosa**
([`11-rss-y-sincronizacion.md`](11-rss-y-sincronizacion.md)).

---

## Información nivológica 🟢

```
GET /api/prediccion/especifica/nivologica/{area}
```

| Código | Área |
|---|---|
| `0` | Pirineo Catalán |
| `1` | Pirineo Navarro y Aragonés |

⚠️ Solo **2** áreas, y con códigos numéricos, frente a las 9 alfanuméricas de montaña. El parámetro
se llama `area` en ambos: dominios incompatibles con el mismo nombre
([`ERRATAS.md` C3](ERRATAS.md#c3-area-reutiliza-el-mismo-nombre-para-5-dominios-incompatibles-)).

`formato: ascii/txt` · `periodicidad:` 🟢 **"Disponible en periodo de campaña. Diaria a las 18:00
h.o.p."**

> [!IMPORTANT]
> 🟢 **Confirmado: es un producto de temporada.** AEMET lo declara "disponible en periodo de campaña"
> (invierno), con emisión diaria a las 18:00 hora oficial peninsular. Fuera de campaña no hay que
> esperar contenido útil.
>
> ⚠️ Sus metadatos declaran **0 campos**: no hay diccionario de datos para este producto.

🟢 Verificado con `0` (Pirineo Catalán) **en agosto**: responde `200` con **texto plano** de 588 B:

```
Agencia Estatal de Meteorología
Información nivológica para el Pirineo Catalán
```

⚠️ **Es texto plano, no JSON**, aunque el spec declare `application/json` — los metadatos lo
confirman: `ascii/txt`. Responde fuera de campaña, así que 🔴 queda por ver si el cuerpo trae
información o un mensaje de "sin datos".

---

## Pendiente de verificar

**Cobertura: 4 de 5.**

| # | Qué | Prioridad |
|---|---|---|
| 1 | Códigos `f1`/`f2` crudos: ❌ **cerrado, sin fuente oficial**. Se documenta el [vocabulario de descripciones](#el-vocabulario-de-descripciones-de-playa-) como alternativa | Cerrado |
| 2 | ✅ *Resuelto*: `CIUDAD` son **59 capitales** con su índice UV | — |
| 3 | ✅ *Resuelto*: estructura de `seccion` en montaña | — |
| 4 | Cuándo se rellenan `apartado` y `lugar` en `seccion` (venían vacíos) | Baja |
| 5 | Si el texto de la nivológica fuera de campaña trae información o un "sin datos" | Baja |
| 6 | Si los campos duplicados de playa (`tAgua`/`tagua`) difieren alguna vez | Media |

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
