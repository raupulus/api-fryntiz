# 📄 Predicciones normalizadas en texto

**22 endpoints** — el grupo más numeroso de la API (34 % del total).

> [!CAUTION]
> **Estos 22 endpoints NO devuelven JSON: devuelven texto plano** 🟢, pese a que la especificación
> declara `application/json` para todos. Y al menos uno devuelve **datos de hace cuatro años** con
> un `200 OK`. Es el grupo menos fiable de toda la API.

Requisitos previos: [`00-fundamentos.md`](00-fundamentos.md) · [`ERRATAS.md`](ERRATAS.md)

Leyenda: 🟢 verificado (2026-08-26) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/especificacion/AEMET_OpenData_specification.json` (los 22 endpoints y sus parámetros) · **verificación en vivo del 2026-08-26** (formato de texto plano y fechas de elaboración medidas endpoint por endpoint).

---

## ⚠️ Aviso de frescura — leer antes de usar este grupo

**Los 22 endpoints están verificados** (2026-08-26). Todos responden `200 OK` con sobre correcto y
contenido bien formado, pero **el dato puede tener años**. Nada en la respuesta HTTP lo indica.

> [!CAUTION]
> **Corrección de una versión anterior de este documento:** aquí se afirmó que "las predicciones
> provinciales están muertas". **Era falso.** Se había comprobado solo Pontevedra y se generalizó.
> La realidad es que **la avería es regional**: ver más abajo.

### Por horizonte: `hoy` y `tendencia` están rotos, el resto va bien 🟢

| Horizonte | Fecha del contenido | Estado |
|---|---|---|
| `nacional/manana` | 25/08/2026 | ✅ al día |
| `nacional/pasadomanana` | 25/08/2026 | ✅ al día |
| `nacional/medioplazo` | 25/08/2026 | ✅ al día |
| `ccaa/manana/gal` | 25/08/2026 | ✅ al día |
| `ccaa/pasadomanana/gal` | 25/08/2026 | ✅ al día |
| `ccaa/medioplazo/gal` | 25/08/2026 | ✅ al día |
| `nacional/hoy` | 24/08/2026 | ⚠️ 2 días |
| **`ccaa/hoy/and`** | 24/08/2026 | ⚠️ 2 días |
| **`ccaa/hoy/mad`** | 20/08/2026 | ⚠️ 6 días |
| **`ccaa/hoy/gal`** | 23/06/2026 | ⚠️ **2 meses** |
| **`nacional/tendencia`** | 29/01/2025 | 🔴 **19 meses** |

🟡 **Patrón: los productos `hoy` van sistemáticamente con retraso, y `tendencia` está abandonado.**
Los de `manana`, `pasadomanana` y `medioplazo` se generan con normalidad. El retraso de `hoy` varía
por comunidad, sin lógica aparente.

### Por provincia: funciona, con una avería conocida 🟢

| Provincia | Fecha del contenido |
|---|---|
| `11` Cádiz | **26/08/2026** — el mismo día |
| `28` Madrid | 25/08/2026 |
| `08` Barcelona | 25/08/2026 |
| `071` Menorca | 25/08/2026 |
| `353` Gran Canaria | 25/08/2026 |

> [!NOTE]
> **Error conocido — no se investiga.** Las cuatro provincias gallegas (`15`, `27`, `32`, `36`)
> devuelven la predicción del **3 de noviembre de 2022**, tanto en `hoy` como en `manana`, y su
> variante de archivo no tiene datos. Es una avería de AEMET en esa región.
>
> **Fuera de alcance**: no se va a analizar ni a monitorizar. Basta con saber que existe y que
> **la validación de frescura la detecta** — que es lo que hay que implementar de todos modos, porque
> el desfase de `hoy` varía por comunidad en toda España.

### Las variantes de archivo reflejan la misma laguna 🟢

`/elaboracion/{fecha}` **no sirve para rodear el problema**: donde el producto está vivo, funciona;
donde está roto, devuelve `estado: 404`.

| Variante de archivo | Resultado |
|---|---|
| `ccaa/manana/gal/elaboracion/2026-08-25` | ✅ 25/08/2026 |
| `ccaa/pasadomanana/gal/elaboracion/2026-08-25` | ✅ 25/08/2026 |
| `ccaa/medioplazo/gal/elaboracion/2026-08-25` | ✅ 25/08/2026 |
| `nacional/manana/elaboracion/2026-08-25` | ✅ 25/08/2026 |
| `nacional/pasadomanana/elaboracion/2026-08-25` | ✅ 25/08/2026 |
| `nacional/medioplazo/elaboracion/2026-08-25` | ✅ 25/08/2026 |
| `provincia/manana/11/elaboracion/2026-08-25` | ✅ 25/08/2026 (Cádiz) |
| **`nacional/hoy/elaboracion/2026-08-25`** | ⚠️ **`estado: 404`** |
| **`ccaa/hoy/gal/elaboracion/2026-08-25`** | ⚠️ **`estado: 404`** |
| **`nacional/tendencia/elaboracion/2026-08-25`** | ⚠️ **`estado: 404`** |
| **`provincia/hoy/{provincia}/elaboracion/{fecha}`** | ⚠️ **`estado: 404`** (probado con `36`, provincia averiada) |

🟡 Es decir: no es que el producto de hoy "aún no se haya generado" y devuelva el anterior. **Es que
no existe.** Los productos `hoy` y `tendencia` han dejado de generarse o de publicarse.

### Qué hacer

1. **Extraer la fecha de la cabecera del texto y validarla** contra el día esperado. La cabecera tiene
   formato fijo: `DÍA 25 DE AGOSTO DE 2026 A LAS 14:12 HORA OFICIAL`.
2. **Prefiere `manana` sobre `hoy`.** Es el producto que sí se mantiene, en los tres ámbitos.
3. **No uses `tendencia`.**
4. **Comprueba la provincia concreta que vayas a usar** antes de darla por buena: la avería es
   regional.
5. Para datos estructurados y frescos, usa la predicción por municipio
   ([`01-predicciones-municipios.md`](01-predicciones-municipios.md)), que sí funciona.

---

## Formato de respuesta 🟢

Texto plano en **ISO-8859-15**, estilo teletipo, con cabecera en mayúsculas:

```
AGENCIA ESTATAL DE METEOROLOGÍA
PREDICCIÓN PARA LA PROVINCIA DE PONTEVEDRA
DÍA 3 DE NOVIEMBRE DE 2022 A LAS 14:00 HORA OFICIAL     ← FRESCURA AQUÍ
PREDICCIÓN VÁLIDA PARA EL JUEVES 3

PONTEVEDRA
.

TEMPERATURAS MÍNIMAS Y MÁXIMAS PREVISTAS (°C):
Pontevedra                    11  18
Vigo                          12  19
```

Las predicciones de CCAA traen además secciones etiquetadas:

```
AGENCIA ESTATAL DE METEOROLOGÍA
PREDICCIÓN GENERAL PARA LA COMUNIDAD DE GALICIA
DÍA 23 DE JUNIO DE 2026 A LAS 09:07 HORA OFICIAL
PREDICCIÓN VÁLIDA PARA EL MARTES 23

A.- FENÓMENOS SIGNIFICATIVOS
Las temperaturas máximas alcanzarán los 39 grados en el sur de
Lugo y en noroeste y Miño de Ourense y los 36-34 grados en el
resto del interior. ...
```

### Consecuencias

- **No hay estructura que parsear de forma fiable.** Es prosa con saltos de línea a ~64 columnas.
- **Está pensado para mostrarse tal cual**, en `<pre>` o convertido a párrafos. Extraer datos
  concretos (una temperatura) requiere expresiones regulares frágiles que se romperán.
- 🟡 Uso razonable: mostrar el texto íntegro como "predicción oficial de AEMET". Para datos
  estructurados, usar [`01-predicciones-municipios.md`](01-predicciones-municipios.md).
- Hay que **convertir la codificación** igual que con el JSON.

---

## Los 22 endpoints

### Ámbito nacional (10)

| Endpoint | Estado |
|---|---|
| `GET /api/prediccion/nacional/hoy` | 🟢 ⚠️ 2 días de desfase |
| `GET /api/prediccion/nacional/hoy/elaboracion/{fecha}` | 🟢 ⚠️ `estado: 404` |
| `GET /api/prediccion/nacional/manana` | 🟢 ✅ al día |
| `GET /api/prediccion/nacional/manana/elaboracion/{fecha}` | 🟢 ✅ |
| `GET /api/prediccion/nacional/pasadomanana` | 🟢 ✅ al día |
| `GET /api/prediccion/nacional/pasadomanana/elaboracion/{fecha}` | 🟢 ✅ |
| `GET /api/prediccion/nacional/medioplazo` | 🟢 ✅ al día |
| `GET /api/prediccion/nacional/medioplazo/elaboracion/{fecha}` | 🟢 ✅ |
| `GET /api/prediccion/nacional/tendencia` | 🟢 🔴 **19 meses de desfase** |
| `GET /api/prediccion/nacional/tendencia/elaboracion/{fecha}` | 🟢 ⚠️ `estado: 404` |

### Por comunidad autónoma (8)

| Endpoint | Estado |
|---|---|
| `GET /api/prediccion/ccaa/hoy/{ccaa}` | 🟢 ⚠️ retrasado en **todas** las regiones (2 días a 2 meses) |
| `GET /api/prediccion/ccaa/hoy/{ccaa}/elaboracion/{fecha}` | 🟢 ⚠️ `estado: 404` |
| `GET /api/prediccion/ccaa/manana/{ccaa}` | 🟢 ✅ al día |
| `GET /api/prediccion/ccaa/manana/{ccaa}/elaboracion/{fecha}` | 🟢 ✅ |
| `GET /api/prediccion/ccaa/pasadomanana/{ccaa}` | 🟢 ✅ al día |
| `GET /api/prediccion/ccaa/pasadomanana/{ccaa}/elaboracion/{fecha}` | 🟢 ✅ |
| `GET /api/prediccion/ccaa/medioplazo/{ccaa}` | 🟢 ✅ al día |
| `GET /api/prediccion/ccaa/medioplazo/{ccaa}/elaboracion/{fecha}` | 🟢 ✅ |

`{ccaa}`: 17 códigos alfabéticos de 3 letras. Tabla en
[`10-catalogos-de-codigos.md`](10-catalogos-de-codigos.md#ccaa-predicciones-en-texto).
⚠️ La especificación escribe Asturias como **"Astrrias"** en 7 de estos 8 endpoints
([`ERRATAS.md` C1](ERRATAS.md#c1-ccaa-asturias-está-escrito-astrrias-)). El código `ast` sí es
correcto.

### Por provincia (4)

| Endpoint | Estado |
|---|---|
| `GET /api/prediccion/provincia/hoy/{provincia}` | 🟢 ✅ al día (⚠️ Galicia averiada) |
| `GET /api/prediccion/provincia/hoy/{provincia}/elaboracion/{fecha}` | 🟢 `estado: 404` sin datos |
| `GET /api/prediccion/provincia/manana/{provincia}` | 🟢 ✅ al día (⚠️ Galicia averiada) |
| `GET /api/prediccion/provincia/manana/{provincia}/elaboracion/{fecha}` | 🟢 ✅ |

`{provincia}`: 59 códigos. Las provincias usan 2 dígitos y **las islas 3**. Son **cadenas** con
ceros a la izquierda. Tabla en
[`10-catalogos-de-codigos.md`](10-catalogos-de-codigos.md#provincias-e-islas).

> A diferencia de CCAA y nacional, **provincia no tiene variantes de "pasado mañana" ni de
> "medio plazo"**: solo hoy y mañana.
>
> 🟢 Verificado: `/api/prediccion/provincia/pasadomanana/11` devuelve **404** (HTML de Tomcat).
> Documentación de terceros que lo mencione está equivocada.

---

## Variantes "tiempo actual" y "elaboración"

Cada producto existe en dos formas:

| Forma | Ruta | Comportamiento |
|---|---|---|
| **Tiempo actual** | `…/hoy/{ccaa}` | 🔵 Devuelve el último elaborado. Si el de hoy no existe aún, devuelve el anterior — **sin avisar**. De aquí puede venir parte del problema de frescura |
| **Archivo** | `…/hoy/{ccaa}/elaboracion/{fecha}` | 🔵 Devuelve el elaborado en la fecha indicada (`AAAA-MM-DD`) |

🟡 La variante de archivo es la única forma de asegurar de qué día es el contenido *antes* de leerlo.
Con la variante "tiempo actual" hay que deducirlo del texto.

---

## Semántica de los horizontes 🔵

| Horizonte | Significado |
|---|---|
| `hoy` | El mismo día de la petición |
| `manana` | Día siguiente (sin `ñ` en la ruta) |
| `pasadomanana` | Dos días después |
| `medioplazo` | Franja de medio plazo a partir de la petición |
| `tendencia` | Solo nacional. Tendencia general más allá del medio plazo |

Periodicidad de todos 🔵: "continuamente".

---

## Pendiente de verificar

**Cobertura: 22 de 22 ✅.**

| # | Qué | Prioridad |
|---|---|---|
| 1 | Antigüedad máxima disponible en las variantes de archivo que funcionan | Baja |
| 2 | Si el texto lleva marcadores de sección estables (`A.-`, `B.-`) en todos los productos | Baja |

> Los desfases de contenido (`hoy`, `tendencia`, provincias gallegas) quedan documentados como
> **errores conocidos de AEMET**, no como pendientes nuestros: no se van a investigar ni monitorizar.
> La validación de frescura los cubre a todos.

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
