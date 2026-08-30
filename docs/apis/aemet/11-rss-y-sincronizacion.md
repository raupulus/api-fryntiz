# 📡 Canales RSS y estrategia de sincronización

AEMET publica **41 canales RSS/ATOM** que anuncian cuándo se actualiza cada producto. Usarlos es
la forma que **AEMET recomienda oficialmente** para no chocar con el límite de peticiones.

Requisitos previos: [`00-fundamentos.md`](00-fundamentos.md) · [`LIMITACIONES.md`](LIMITACIONES.md)

Leyenda: 🟢 verificado (2026-08-26) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/catalogos/canales_rss.json` (**los 41 canales con sus rutas**) · `src/web-texto/rssatom.txt` (la página que los presenta) · `src/web-texto/gitlab-python-README.md` (el patrón "RSS primero, endpoint después" que **recomienda AEMET**) · `src/catalogos/faqs.json` (FAQ 1.11–1.14) · **metadatos** de los productos (periodicidades).

---

## El patrón que recomienda AEMET 🔵

Del README del cliente Python oficial (`src/web-texto/gitlab-python-README.md`,
<https://gitlab.aemet.es/opendata/API>, MIT, © 2026 AEMET):

> Cuando un dataset dispone de RSS, se recomienda utilizarlo para detectar actualizaciones antes de
> consultar directamente el endpoint. […] Este enfoque reduce significativamente el número de
> peticiones realizadas a la API, evitando así la aparición de respuestas con el código de estado
> **429 (Too Many Requests)**.

```
   RSS  ──sin cambios──▶  no hacer nada  (coste: 1 petición ligera, no cuenta contra la API)
    │
    │ actualización detectada
    ▼
   PASO 1 (endpoint, con api_key)
    ▼
   PASO 2 (datos, sin api_key)
    ▼
   metadatos (opcional)
    ▼
   persistir
```

🟡 **Por qué importa aquí en particular:** el límite de AEMET no es un único contador, sino cubos por
familia de endpoint bastante estrechos
([`LIMITACIONES.md`](LIMITACIONES.md#el-cubo-es-por-plantilla-de-endpoint-no-global-)). Sondear un endpoint "por si
acaso" gasta el cubo de esa familia y provoca 429 que afectan al resto de consultas del mismo grupo.
El RSS mueve ese sondeo fuera de la API.

🔴 **Sin verificar** si las peticiones al RSS cuentan contra el límite. 🟡 Probablemente no: son
ficheros estáticos en `opendata.aemet.es/rss/`, no rutas `/opendata/api/`, y no requieren API Key.
**Comprobar antes de apoyarse en esto a gran escala.**

---

## Los 41 canales 🟢

Fuente: `src/catalogos/canales_rss.json`.

> ⚠️ El JSON declara `"total": 800`. **Es un artefacto** de la tabla de la web: los canales reales
> son **41**. No te fíes de ese campo.

Cada canal existe en dos formatos: **RSS** y **ATOM**. Ninguno de los 41 ofrece **GeoRSS** 🟢, pese
a que la web lo anuncia como opción.

### Novedades del servicio (1)

| Tema | RSS |
|---|---|
| Noticias — actualidad de OpenData | `/centrodedescargas/feeds.rss` · ATOM: `/centrodedescargas/feeds.atom` |

🟡 **Este canal es el más importante para el mantenimiento**, no para los datos: es donde AEMET
anuncia cortes, incidencias, endpoints nuevos y cambios de política (como la caducidad de las API
Keys). Conviene vigilarlo.

### Predicciones (15)

Un canal para municipio, uno para playa, tres para montaña (todos / actual / pasado), **nueve por
área montañosa** y uno para UVI.

⚠️ **"Predicción municipio" es un solo canal para todos los municipios**, no uno por municipio.
🔴 Sin verificar si el feed identifica *qué* municipios cambiaron o solo anuncia que hubo
actualización. Si es lo segundo, sirve como disparador global, no como filtro.

### Observación (5)

Observación convencional horaria, y los mensajes `climat`, `synop`, `temp` y "todos".

### Avisos (20)

Un canal para España y **uno por comunidad autónoma** (las 17 más Ceuta y Melilla).

🟡 Es el grupo donde el RSS aporta más: los avisos se emiten de forma **impredecible**, así que la
alternativa es sondear cada pocos minutos un endpoint que devuelve **490 KB por comunidad** o
**3,4 MB para España**. El RSS convierte eso en una comprobación ligera.

---

## Los 41 canales, con sus rutas exactas 🟢

Rutas relativas a `https://opendata.aemet.es`. Extraídas de `src/catalogos/canales_rss.json`
(la página que los presenta es `src/web-texto/rssatom.txt`). **Ninguno requiere API Key** 🟡.

| # | Tema | RSS | ATOM |
|---|---|---|---|
| 1 | Noticias | `/centrodedescargas/feeds.rss` | `/centrodedescargas/feeds.atom` |
| 2 | Predicción municipio | `/rss/predesp_mun_opendata_todos_RSS.xml` | `/rss/predesp_mun_opendata_todos_ATOM.xml` |
| 3 | Predicción playa | `/rss/predesp_ply_opendata_todos_RSS.xml` | `/rss/predesp_ply_opendata_todos_ATOM.xml` |
| 4 | Predicción montaña todos | `/rss/predesp_mon_opendata_todos_todos_RSS.xml` | `/rss/predesp_mon_opendata_todos_todos_ATOM.xml` |
| 5 | Predicción montaña actual | `/rss/predesp_mon_opendata_todos_predcc_RSS.xml` | `/rss/predesp_mon_opendata_todos_predcc_ATOM.xml` |
| 6 | Predicción montaña pasado | `/rss/predesp_mon_opendata_todos_pasado_RSS.xml` | `/rss/predesp_mon_opendata_todos_pasado_ATOM.xml` |
| 7 | Predicción montaña área: Picos de Europa | `/rss/predesp_mon_opendata_peu1_todos_RSS.xml` | `/rss/predesp_mon_opendata_peu1_todos_ATOM.xml` |
| 8 | Predicción montaña área: Pirineo Navarro | `/rss/predesp_mon_opendata_nav1_todos_RSS.xml` | `/rss/predesp_mon_opendata_nav1_todos_ATOM.xml` |
| 9 | Predicción montaña área: Pirineo Aragonés | `/rss/predesp_mon_opendata_arn1_todos_RSS.xml` | `/rss/predesp_mon_opendata_arn1_todos_ATOM.xml` |
| 10 | Predicción montaña área: Pirineo Catalán | `/rss/predesp_mon_opendata_cat1_todos_RSS.xml` | `/rss/predesp_mon_opendata_cat1_todos_ATOM.xml` |
| 11 | Predicción montaña área: Ibérica Riojana | `/rss/predesp_mon_opendata_rio1_todos_RSS.xml` | `/rss/predesp_mon_opendata_rio1_todos_ATOM.xml` |
| 12 | Predicción montaña área: Ibérica Aragonesa | `/rss/predesp_mon_opendata_arn2_todos_RSS.xml` | `/rss/predesp_mon_opendata_arn2_todos_ATOM.xml` |
| 13 | Predicción montaña área: Sierras de Guadarrama y Somosierra | `/rss/predesp_mon_opendata_mad2_todos_RSS.xml` | `/rss/predesp_mon_opendata_mad2_todos_ATOM.xml` |
| 14 | Predicción montaña área: Sierra de Gredos | `/rss/predesp_mon_opendata_gre1_todos_RSS.xml` | `/rss/predesp_mon_opendata_gre1_todos_ATOM.xml` |
| 15 | Predicción montaña área: Sierra Nevada | `/rss/predesp_mon_opendata_nev1_todos_RSS.xml` | `/rss/predesp_mon_opendata_nev1_todos_ATOM.xml` |
| 16 | Predicción de radiación ultravioleta (UVI) | `/rss/predesp_uvi_opendata_todos_RSS.xml` | `/rss/predesp_uvi_opendata_todos_ATOM.xml` |
| 17 | Observación convencional horaria | `/rss/obsconv_hh_opendata_todos_RSS.xml` | `/rss/obsconv_hh_opendata_todos_ATOM.xml` |
| 18 | Observación convencional mensajes: climat | `/rss/obsconv_mens_opendata_climat_RSS.xml` | `/rss/obsconv_mens_opendata_climat_ATOM.xml` |
| 19 | Observación convencional mensajes: temp | `/rss/obsconv_mens_opendata_temp_RSS.xml` | `/rss/obsconv_mens_opendata_temp_ATOM.xml` |
| 20 | Observación convencional mensajes: synop | `/rss/obsconv_mens_opendata_synop_RSS.xml` | `/rss/obsconv_mens_opendata_synop_ATOM.xml` |
| 21 | Observación convencional mensajes: todos | `/rss/obsconv_mens_opendata_todos_RSS.xml` | `/rss/obsconv_mens_opendata_todos_ATOM.xml` |
| 22 | Avisos. Área: España | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAE_RSS.xml` | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAE_ATOM.xml` |
| 23 | Avisos. Área: Andalucía | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC61_RSS.xml` | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC61_ATOM.xml` |
| 24 | Avisos. Área: Aragón | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC62_RSS.xml` | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC62_ATOM.xml` |
| 25 | Avisos. Área: Canarias | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC65_RSS.xml` | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC65_ATOM.xml` |
| 26 | Avisos. Área: Cantabria | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC66_RSS.xml` | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC66_ATOM.xml` |
| 27 | Avisos. Área: Castilla - La Mancha | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC68_RSS.xml` | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC68_ATOM.xml` |
| 28 | Avisos. Área: Castilla y Leon | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC67_RSS.xml` | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC67_ATOM.xml` |
| 29 | Avisos. Área: Cataluña | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC69_RSS.xml` | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC69_ATOM.xml` |
| 30 | Avisos. Área: Ciudad de Ceuta | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC78_RSS.xml` | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC78_ATOM.xml` |
| 31 | Avisos. Área: Ciudad de Melilla | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC79_RSS.xml` | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC79_ATOM.xml` |
| 32 | Avisos. Área: Comunidad de Madrid | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC72_RSS.xml` | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC72_ATOM.xml` |
| 33 | Avisos. Área: Comunidad Foral de Navarra | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC74_RSS.xml` | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC74_ATOM.xml` |
| 34 | Avisos. Área: Comunitat Valenciana | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC77_RSS.xml` | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC77_ATOM.xml` |
| 35 | Avisos. Área: Extremadura | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC70_RSS.xml` | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC70_ATOM.xml` |
| 36 | Avisos. Área: Galicia | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC71_RSS.xml` | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC71_ATOM.xml` |
| 37 | Avisos. Área: Illes Balears | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC64_RSS.xml` | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC64_ATOM.xml` |
| 38 | Avisos. Área: La Rioja | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC76_RSS.xml` | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC76_ATOM.xml` |
| 39 | Avisos. Área: Principado de Asturias | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC63_RSS.xml` | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC63_ATOM.xml` |
| 40 | Avisos. Área: País Vasco | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC75_RSS.xml` | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC75_ATOM.xml` |
| 41 | Avisos. Área: Región de Murcia | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC73_RSS.xml` | `https://www.aemet.es/documentos_d/eltiempo/prediccion/avisos/rss/CAP_AFAC73_ATOM.xml` |

⚠️ Los feeds ATOM son los mismos ficheros con el sufijo `_ATOM.xml` en vez de `_RSS.xml`.
⚠️ **Ninguno de los 41 ofrece GeoRSS** 🟢, aunque la web lo anuncie.

---

## Qué productos NO tienen RSS 🟢

Deducido del listado completo de los 41 canales:

| Sin RSS | Implicación |
|---|---|
| **Predicciones normalizadas en texto** (22 endpoints) | Sondeo programado, sin más opción |
| **Valores y productos climatológicos** (10) | 🟡 Da igual: cambian con días de retraso, un cron diario sobra |
| **Predicción marítima** (2) | 🟡 Sondeo a horas fijas, que se conocen: 12:00/20:00 h.o.p. la costera y 08:00/20:00 UTC la de alta mar |
| **Radar, satélite, rayos, mapas** (7) | Sondeo según periodicidad |
| **Índices de incendios** (2) | Sondeo diario |
| **Redes especiales y Antártida** (5) | Sondeo poco frecuente |
| **Maestro de municipios** (2) | 🟡 Irrelevante: descargar una vez |
| **Nivológica** (1) | Sondeo de temporada |

🟡 Para los productos sin RSS, la periodicidad de los `metadatos` es la que dice cada cuánto merece
la pena sondear ([`LIMITACIONES.md`](LIMITACIONES.md#periodicidad-real-de-actualización)).

---

## Formatos disponibles

| Formato | Disponible | Nota |
|---|---|---|
| **RSS 2.0** | ✅ los 41 | Sufijo `_RSS.xml` |
| **ATOM** | ✅ los 41 | Sufijo `_ATOM.xml` |
| **GeoRSS** | ❌ ninguno 🟢 | La web lo ofrece pero los 41 canales dicen "No disponible" |

🔴 Sin verificar: la estructura interna de los feeds (qué campo marca la fecha de actualización, si
hay un `<guid>` estable, si listan los elementos concretos que cambiaron).

---

## Estrategia de sincronización propuesta

🟡 Inferido de todo lo anterior. **No implementado**: esto es documentación de la API, el diseño de
la integración irá en `docs/info/apis/`.

| Producto | Disparador | Frecuencia de comprobación 🟡 |
|---|---|---|
| Avisos CAP | **RSS** de la comunidad | 10-15 min |
| Predicción municipio | **RSS** "Predicción municipio" | 30 min |
| Predicción playa | **RSS** "Predicción playa" | 1 h |
| Observación | **RSS** "Observación convencional horaria" | 30 min |
| Marítima costera | Cron a horas fijas | tras 12:00 y 20:00 h.o.p. |
| Marítima alta mar | Cron a horas fijas | tras 08:00 y 20:00 UTC |
| Mapas de análisis | Cron | tras 00:00 y 12:00 |
| Predicciones en texto | Cron | 2-4 veces al día ⚠️ validando frescura |
| Climatologías | Cron | diario o semanal |
| Maestro de municipios | Manual | una vez |
| Novedades del servicio | **RSS** "Noticias" | diario — vigila cambios de política |

### Reglas que se derivan

1. **Escalonar los cron.** Si todo se dispara a las 00:00 se agotan varios cubos a la vez. Repartir.
2. **Rotar entre familias** en lugar de agotar una.
3. **Guardar la última fecha vista de cada feed** para no reprocesar.
4. **El fallo del RSS no debe impedir la actualización.** Si el feed no responde, hay que tener un
   sondeo de respaldo con la frecuencia mínima; si no, un RSS caído congela los datos en silencio.
5. **Vigilar el canal de Noticias.** Es como se supo de la caducidad de las API Keys.

---

## Pendiente de verificar

| # | Qué | Prioridad |
|---|---|---|
| 1 | Si las peticiones al RSS cuentan contra el límite de la API | 🔥 Alta — toda la estrategia depende de ello |
| 2 | Estructura del feed: campo de fecha, `guid` estable | Alta |
| 3 | Si "Predicción municipio" identifica los municipios cambiados o solo anuncia el cambio | Alta |
| 4 | Latencia entre la actualización del dato y su aparición en el feed | Media |
| 5 | URLs exactas de los canales de avisos y observación (solo se han extraído las de predicción) | Media |
| 6 | Si los feeds requieren API Key | Media — 🟡 aparentemente no |

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
