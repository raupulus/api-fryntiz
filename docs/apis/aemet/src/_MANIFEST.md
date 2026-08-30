# Manifiesto de fuentes originales — AEMET OpenData

> [!WARNING]
> **Este es el ÚNICO archivo de `src/` escrito por nosotros.** Todo lo demás son documentos
> oficiales de AEMET/INE capturados tal cual. **No se edita nada de `src/`.**
> Para consultar la API, usa la documentación en `docs/apis/aemet/*.md`, no estos originales.

- **Fecha de captura:** 2026-08-26 (documentos añadidos el mismo día)
- **Método:** descarga directa (`curl`) desde los dominios oficiales `opendata.aemet.es`,
  `www.aemet.es`, `gitlab.aemet.es` e `www.ine.es`.
- **Versión de la API en el momento de la captura:** OpenAPI `3.0.1`, `info.version = 2.0`
- **Servidor base:** `https://opendata.aemet.es/opendata`

---

## Inventario

### `especificacion/`

| Archivo | Origen | Fiabilidad |
|---|---|---|
| `AEMET_OpenData_specification.json` | https://opendata.aemet.es/AEMET_OpenData_specification.json | 🟢 **Oficial.** Especificación OpenAPI 3.0.1 publicada por AEMET. 64 endpoints, todos `GET`. Fuente autoritativa de rutas y parámetros. Contiene erratas conocidas (ver `docs/apis/aemet/README.md`). |

### `catalogos/`

| Archivo | Origen | Fiabilidad |
|---|---|---|
| `faqs.json` | https://opendata.aemet.es/centrodedescargas/json/faqs.json | 🟢 **Oficial.** 5 categorías, 46 preguntas. Única fuente que documenta el **límite de 40 consultas/minuto** y la política de caducidad de API Keys. |
| `canales_rss.json` | https://opendata.aemet.es/centrodedescargas/json/canales_rss.json | 🟢 **Oficial.** 41 canales RSS/ATOM reales. ⚠️ El campo `total: 800` del JSON es un artefacto de la tabla de la web, **no** el número de canales. |
| `Playas_codigos.csv` | https://www.aemet.es/documentos/es/eltiempo/prediccion/playas/Playas_codigos.csv | 🟢 **Oficial AEMET.** 590 playas con `ID_PLAYA`, provincia, municipio y coordenadas. Codificación **ISO-8859**, separador `;`, saltos CRLF. Referenciado desde el propio spec. |
| `diccionario_municipios_INE.xlsx` | https://www.ine.es/daco/daco42/codmun/diccionario24.xlsx | 🟡 **Oficial pero de terceros (INE, no AEMET).** Referenciado por el spec como fuente de `{municipio}`. Para producción es preferible `GET /api/maestro/municipios`, que es la lista que la propia AEMET reconoce. |

### `documentos/`

| Archivo | Origen | Fiabilidad |
|---|---|---|
| `PLAN_METEOALERTA_v9_web_externa.pdf` | https://www.aemet.es/es/eltiempo/prediccion/avisos/ayuda | 🟢 **Oficial.** Cuerpo del Plan, v9 de 10-ene-2025. ⚠️ **Duplicado** de `plan_meteoalerta.pdf`: el mismo documento por dos rutas. Se conservan los dos porque `src/` no se edita; el destilado se hizo del primero. |
| `METEOALERTA_ANX1_Umbrales_y_niveles_de_aviso.pdf` | ídem | 🟢 **Oficial.** v1, 31-may-2022, 20 páginas. Criterios cualitativos por fenómeno y **tablas de umbrales de las 233 zonas** (6 variables × 3 niveles). Las tablas son **imágenes**: no transcritas. |
| `METEOALERTA_ANX2_Zonas_aviso.pdf` | ídem | 🟢 **Oficial.** Relación de zonas de aviso. Redundante con el shapefile, que es estructurado. |
| `METEOALERTA_ANX3_CAP.pdf` | ídem | 🟢 **Oficial.** v1, 31-may-2022, 14 páginas. **Especificación CAP de AEMET**: valores admitidos de cada etiqueta, los 13 códigos de fenómeno y 14 de parámetro, nomenclatura de ficheros y ejemplo completo. Es la fuente a la que remiten los metadatos del endpoint. |
| `METEOALERTA_ANX4_Boletin_avisos.pdf` | ídem | 🟢 **Oficial.** Formato de los boletines de avisos (producto alternativo al CAP). 🔴 No destilado. |
| `AEMET-meteoalerta-delimitacion-zonas.zip` | http://www.aemet.es/documentos/es/eltiempo/prediccion/avisos/plan_meteoalerta/AEMET-meteoalerta-delimitacion-zonas.zip | 🟢 **Oficial.** Shapefiles con la **geometría de las 233 zonas** (182 terrestres + 51 costeras hasta 20 millas). EPSG:32630, ISO-8859-15. ⚠️ Corresponde a la **v6** del Plan (feb-2018); el Plan vigente es v9. Obra derivada de BDLJE del IGN (CC-BY ign.es). |
| `detalle_municipios_zonas_meteorologicas.pdf` | https://www.aemet.es/es/eltiempo/prediccion/avisos/ayuda | 🟢 **Oficial.** 221 páginas con la correspondencia **municipio → zona de aviso**. ❌ **No se transcribe y no hace falta**: 🟢 verificado que `GET /api/maestro/municipios` devuelve `zona_comarcal` en **los 8.122 municipios** en **una sola petición**, referenciando las 182 zonas terrestres sin huérfanas. Se conserva como respaldo por si la API dejara de exponer el campo. |
| `plan_meteoalerta.pdf` | https://www.aemet.es/documentos/es/eltiempo/prediccion/avisos/plan_meteoalerta/plan_meteoalerta.pdf | 🟢 **Oficial AEMET.** *Plan Nacional de Predicción y Vigilancia de Fenómenos Meteorológicos Adversos: Meteoalerta*, **versión 9 de 10-ene-2025**, 13 páginas. Documento **normativo** de los avisos: niveles, fenómenos, umbrales, periodos de emisión y zonificación. Es la fuente a la que remiten los metadatos del endpoint de avisos. ⚠️ Sus **Anexos 1-4 son documentos separados y no se han localizado**. |

### `web-original/` — HTML tal cual se sirvió

Fidelidad byte a byte de las páginas oficiales. Codificaciones mezcladas (`ISO-8859-15` y `UTF-8`).
Varias páginas cargan su contenido por JavaScript, por lo que el HTML **no** contiene todos los datos
(de ahí los `.json` de `catalogos/`).

| Archivo | URL de origen |
|---|---|
| `centrodedescargas-inicio.html` | https://opendata.aemet.es/centrodedescargas/inicio |
| `info.html` | https://opendata.aemet.es/centrodedescargas/info |
| `faqs.html` | https://opendata.aemet.es/centrodedescargas/faqs (contenido real en `catalogos/faqs.json`) |
| `novedades.html` | https://opendata.aemet.es/centrodedescargas/novedades |
| `AEMETApi.html` | https://opendata.aemet.es/centrodedescargas/AEMETApi |
| `ejemProgramas.html` | https://opendata.aemet.es/centrodedescargas/ejemProgramas |
| `productosAEMET.html` | https://opendata.aemet.es/centrodedescargas/productosAEMET |
| `rssatom.html` | https://opendata.aemet.es/centrodedescargas/rssatom (contenido real en `catalogos/canales_rss.json`) |
| `altaUsuario.html` | https://opendata.aemet.es/centrodedescargas/altaUsuario |
| `nota_legal.html` | https://www.aemet.es/es/nota_legal |

### `web-texto/` — transcripciones legibles

Conversión **mecánica** HTML → texto de los archivos de `web-original/` (se eliminan etiquetas,
se normaliza la codificación a UTF-8). **No se ha alterado ni resumido el contenido.** Existen para
poder buscar (`grep`) sin lidiar con el HTML ni con las codificaciones mixtas.

| Archivo | Fiabilidad y contenido destacable |
|---|---|
| `faqs.txt` | 🟢 Transcripción de `catalogos/faqs.json`. **El documento más valioso de todo `src/`**: límites de uso, caducidad de API Keys, códigos HTTP con ejemplos y flujo de 2 pasos. |
| `novedades.txt` | 🟢 Changelog oficial. Registra la introducción de la caducidad de API Keys (16/07/2026) y los endpoints `.../todos` nuevos. |
| `ejemProgramas.txt` | 🟢 Ejemplos de cliente oficiales en 14 lenguajes. ⚠️ Todos pasan la API Key por **query string**; las claves que aparecen son muestras públicas de AEMET, caducadas y parcialmente alteradas — **no usar**. |
| `info.txt` | 🟢 Descripción del servicio, marco legal y tipos de acceso. |
| `nota_legal.txt` | 🟢 Condiciones de reutilización y **obligación de citar a AEMET**. De lectura obligada antes de publicar datos en el frontal. |
| `productosAEMET.txt` | 🟢 Catálogo de productos del acceso general. Útil para ver qué existe en la web pero **no** vía API. |
| `AEMETApi.txt`, `rssatom.txt`, `altaUsuario.txt`, `centrodedescargas-inicio.txt` | 🟢 Páginas de apoyo (HATEOAS, RSS, obtención de API Key). Poco contenido. |
| `gitlab-python-README.md` | 🟢 **Oficial AEMET** (https://gitlab.aemet.es/opendata/API, licencia MIT, © 2026 AEMET). Cliente Python de referencia. Documenta el patrón recomendado por AEMET: **usar RSS para detectar cambios antes de llamar al endpoint** y así evitar el 429. |

---

## Descartado deliberadamente

**`php-client-generated.zip`** (cliente PHP generado con Swagger Codegen 2.4.0, fechado 13/12/2018).
No se conserva. Motivos:

1. **Redundante:** contiene 62 de los 64 endpoints actuales; el único que sobra es
   `/api/mapasygraficos/mapassignificativos/{ambito}/{dia}` (variante sin fecha, ya retirada del spec) y le faltan tres
   endpoints actuales (`/api/antartida/...` y los dos `/api/prediccion/especifica/municipio/{diaria,horaria}/todos`).
   Toda su información deriva del mismo `AEMET_OpenData_specification.json` que sí conservamos, en una versión más vieja.
2. **Obsoleto técnicamente:** exige `php >= 5.5`, `guzzlehttp/guzzle ^6.2` y `phpunit ^4.8`, incompatible
   con el stack del proyecto.
3. **Innecesario:** la API son 64 `GET` con una cabecera; el cliente HTTP de Laravel lo cubre sin
   añadir ~1 MB de código generado y sin mantenimiento.

Lo único que merecía retenerse era el dato de que AEMET ofrece un generador ("Aemet Codegen") sobre
https://github.com/swagger-api/swagger-codegen y https://editor.swagger.io/, ya recogido en la
documentación derivada.

---

## Cómo actualizar estas fuentes

Las fuentes son una **foto del 2026-08-26**. AEMET publica cambios en
https://opendata.aemet.es/centrodedescargas/novedades y en su canal RSS de Noticias
(`https://opendata.aemet.es/centrodedescargas/feeds.rss`). Al refrescar:

1. Vuelve a descargar los archivos afectados a la misma ruta.
2. Actualiza la **fecha de captura** de la cabecera de este manifiesto.
3. Revisa si la documentación derivada de `docs/apis/aemet/*.md` sigue siendo correcta y
   actualiza su fecha de verificación.
