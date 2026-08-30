# 🛰️ Imágenes y mapas: radar, satélite, rayos y mapas

**7 endpoints.** Productos **gráficos**, no datos estructurados.

Requisitos previos: [`00-fundamentos.md`](00-fundamentos.md) · [`ERRATAS.md`](ERRATAS.md)

Leyenda: 🟢 verificado (2026-08-26) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/especificacion/AEMET_OpenData_specification.json` (rutas y códigos de radar, ámbito y día) · **metadatos de cada producto** (`formato: image/gif|png` y periodicidad) · **verificación en vivo del 2026-08-26** (formato real, tamaños y los dos endpoints que no devuelven datos).

---

## Resumen 🟢

**6 de 7 verificados.** Y las sorpresas son varias.

| Endpoint | Estado | Formato real | Tamaño | Periodicidad |
|---|---|---|---|---|
| `GET /api/red/radar/regional/{radar}` | 🟢 | **GIF** | 23,1 KB | "cada 10 minutos" |
| `GET /api/red/rayos/mapa` | 🟢 | **GIF** | 13,3 KB | "Cada seis horas o 00Z, 06Z, 12Z, 18Z" |
| `GET /api/mapasygraficos/analisis` | 🟢 | **GIF** | 117,8 KB | ⚠️ ver nota abajo |
| `GET /api/satelites/producto/sst` | 🟢 | **GIF** | 100,7 KB | "1 vez al día" |
| `GET /api/satelites/producto/nvdi` | 🟢 | **GIF** | 246,2 KB | "1 vez al día" |
| `GET /api/red/radar/nacional` | 🟢 | ⚠️ **`estado: 404`** | — | — |
| `GET /api/mapasygraficos/mapassignificativos/…` | 🟢 | ⚠️ **`estado: 404`** | — | — |

> [!WARNING]
> ⚠️ **Dos de los siete no devuelven nada** 🟢, aunque respondan `HTTP 200`:
>
> - **`/api/red/radar/nacional`** → sobre con `estado: 404`, `"Error al obtener los datos"`.
>   Mientras que **el radar regional sí funciona**. Usa el regional.
> - **`/api/mapasygraficos/mapassignificativos/…`** → `estado: 404`,
>   `"No hay datos que satisfagan esos criterios"`, probado con fecha reciente. Confirma el
>   "Hasta el 22/01/2020" de su propia descripción: **está descatalogado**.

### Los metadatos confirman el formato 🟢

El campo `formato` de los metadatos coincide con lo medido, y **desmiente la especificación**:

| Producto | `formato` de los metadatos | `campos` |
|---|---|---|
| Radar regional | **`image/gif`** | 0 |
| Satélite SST y NDVI | **`image/gif`** | 0 |
| Mapa de rayos | **`image/gif`** | 0 |
| Mapas de análisis | **`image/gif`** | 0 |
| Incendios previsto | **`image/png`** | 0 |

**Los cinco declaran `campos: 0`**, lo cual es correcto: son imágenes, no tienen campos.
Es un caso en que los metadatos son fiables y la especificación (`application/json`) no.

### Detalles del formato 🟢

- **Cinco son GIF, no PNG.** Y llegan con `Content-Type: image/gif;charset=ISO-8859-15` — **un
  charset declarado en un binario**, que no significa nada. **No apliques ninguna conversión de
  codificación a estos cuerpos**: los corrompería.
- El único PNG de la API está en incendios (`image/png`, sin charset) — ver
  [`04-avisos-y-riesgos.md`](04-avisos-y-riesgos.md).
- **El flujo de dos saltos sí aplica** aquí: el paso 1 devuelve el sobre JSON y la imagen está en la
  URL `datos`.

---

## Red de radares 🟢

```
GET /api/red/radar/nacional          ⚠️ devuelve estado: 404
GET /api/red/radar/regional/{radar}  ✅ GIF, 23 KB, cada 10 minutos
```

🟢 El **regional funciona** (verificado con `co`, A Coruña): GIF de 23,1 KB, con periodicidad
declarada en los metadatos de **"cada 10 minutos"** — es el producto más frecuente de toda la API.

⚠️ El **nacional está roto** 🟢: sobre con `estado: 404` y `"Error al obtener los datos"`.
🔴 Sin saber si es permanente o intermitente. **Si necesitas cobertura nacional, tendrás que
componer los 15 regionales o esperar a que AEMET lo arregle.**

### Códigos de radar 🔵 — 15 valores

| Código | Radar | | Código | Radar |
|---|---|---|---|---|
| `am` | Almería | | `ml` | Málaga |
| `sa` | Asturias | | `mu` | Murcia |
| `pm` | Illes Balears | | `vd` | Palencia |
| `ba` | Barcelona | | `ca` | Las Palmas |
| `cc` | Cáceres | | `se` | Sevilla |
| `co` | A Coruña | | `va` | Valencia |
| `ma` | Madrid | | `ss` | Vizcaya |
| | | | `za` | Zaragoza |

⚠️ Códigos de **2 letras** que no siguen ninguna regla deducible: Asturias es `sa`, Vizcaya es `ss`,
Las Palmas es `ca`. **No intentes derivarlos del nombre**, usa la tabla.

🟡 La FAQ 4.9 confirma el uso con `am` (Almería) y muestra la respuesta como envelope JSON normal.

---

## Mapa de rayos 🟢

```
GET /api/red/rayos/mapa
```

🟢 GIF de 13,3 KB, `formato: image/gif`. Periodicidad de los metadatos:
**"Cada seis horas o 00Z, 06Z, 12Z, 18Z"** — así que el "periodo estándar" del spec son **6 horas**,
y las horas son **UTC** (`Z`), no locales.

---

## Productos de satélite 🟢

```
GET /api/satelites/producto/nvdi
GET /api/satelites/producto/sst
```

| Ruta | Producto |
|---|---|
| `nvdi` | Índice normalizado de vegetación |
| `sst` | Temperatura del agua del mar (*sea surface temperature*) |

> [!WARNING]
> ⚠️ **La ruta es `nvdi`, no `ndvi`.** El índice real se llama NDVI (*Normalized Difference
> Vegetation Index*): es una errata consolidada en la ruta. **Hay que escribirlo mal para que
> funcione.** Ver [`ERRATAS.md` C9](ERRATAS.md#c9-el-endpoint-de-satélite-se-llama-nvdi-no-ndvi-).

🟢 Ambos verificados: **GIF**, `formato: image/gif` en los metadatos, **"1 vez al día"** los dos.
SST 100,7 KB, NDVI 246,2 KB. 🔴 Cobertura geográfica sin determinar.

---

## Mapas de análisis 🟢

```
GET /api/mapasygraficos/analisis
```

🔵 Presión en superficie con isobaras, centros de altas (A, a) y bajas (B, b) presiones y frentes,
para Europa y el Atlántico Norte.

🟢 Verificado: **GIF de 117,8 KB**.

> [!WARNING]
> ⚠️ **La especificación y los metadatos no coinciden en la periodicidad:**
>
> | Fuente | Periodicidad |
> |---|---|
> | Especificación 🔵 | "cada 12 horas (00, 12)" |
> | **Metadatos** 🟢 | **"Dos veces al día, a las 02:00 y 14:00 h.o.p. en invierno y a las 03:00 y 15:00 en verano"** |
>
> Los metadatos son más concretos y **además avisan de que las horas cambian con el horario de
> verano**. Un cron fijo a las 00:00 y 12:00 pediría el mapa antes de que exista.

TTL 🟡 sugerido: 6 h.

---

## Mapas significativos ⚠️ DESCATALOGADO 🟢

```
GET /api/mapasygraficos/mapassignificativos/fecha/{fecha}/{ambito}/{dia}
```

> [!CAUTION]
> 🟢 **Verificado el 2026-08-26: no devuelve datos.** Probado con
> `fecha/2026-08-25/gal/a` → sobre con `estado: 404`,
> `"No hay datos que satisfagan esos criterios"`. Confirma el "Hasta el 22/01/2020" de su propia
> descripción. **Trátalo como descatalogado.** 🔴 Sin comprobar si sirve fechas anteriores a 2020.

| Parámetro | Valores |
|---|---|
| `{fecha}` | `AAAA-MM-DD` (fecha de elaboración) |
| `{ambito}` | `esp` + 17 códigos de CCAA de 3 letras ([tabla](10-catalogos-de-codigos.md#ámbitos-para-mapas-significativos)) |
| `{dia}` | `a`–`f`: tramos de 12 h |

`{dia}` — sexta escala distinta para un parámetro llamado `dia`
([`ERRATAS.md` C4](ERRATAS.md#c4-dia-reutiliza-el-nombre-para-4-escalas-distintas-)):

| Código | Tramo |
|---|---|
| `a` | D+0 (00-12) |
| `b` | D+0 (12-24) |
| `c` | D+1 (00-12) |
| `d` | D+1 (12-24) |
| `e` | D+2 (00-12) |
| `f` | D+2 (12-24) |

⚠️ `{ambito}` usa **18** códigos (los 17 de CCAA más `esp`), mientras que `{ccaa}` en las
predicciones de texto usa solo 17. Mismo juego de letras, dominios distintos.

> 🟢 El cliente PHP que AEMET generó en 2018 incluía además
> `/api/mapasygraficos/mapassignificativos/{ambito}/{dia}` (sin fecha). **Ya no existe** en el spec
> actual. Detalle en [`src/_MANIFEST.md`](src/_MANIFEST.md#descartado-deliberadamente).

---

## Consideraciones para todos los productos gráficos

1. **El flujo de dos saltos aplica** 🟢: paso 1 → sobre JSON; la imagen está en `datos`.
2. ⚠️ **No apliques la conversión de codificación a un binario.** La regla ISO-8859-15 → UTF-8 vale
   para texto; sobre un GIF lo corrompe. Y **los GIF declaran `charset=ISO-8859-15`**, así que una
   ramificación por "¿tiene charset?" no basta: hay que mirar el tipo MIME.
3. ✅ **Decidido: siempre descargar y servir en local.** La URL `datos` es pública y no requiere
   autenticación 🟢, pero es **efímera**. No se enlaza nunca: se descarga el binario, se persiste y se
   sirve desde nuestro almacenamiento. Así se cumple además la atribución
   ([`12-uso-legal-y-atribucion.md`](12-uso-legal-y-atribucion.md)) y no dependemos de AEMET.
4. **Tamaños medidos** 🟢: entre 13 KB (rayos) y 246 KB (NDVI). Manejables, pero el NDVI a diario
   suma.
5. **La atribución a AEMET es obligatoria también en las imágenes**, y aquí no viene un campo
   `copyright` en el que apoyarse: hay que añadirla en la plantilla.
6. **Dos de los siete no devuelven nada.** Cualquier proceso debe tolerarlo sin romperse.

---

## Pendiente de verificar

**Cobertura: 6 de 7.**

| # | Qué | Prioridad |
|---|---|---|
| 1 | Si `radar/nacional` y `mapassignificativos` están rotos de forma permanente | Alta — reintentar en días distintos |
| 3 | Periodicidad del NDVI y cobertura geográfica de los productos de satélite | Media |
| 4 | Si `mapassignificativos` sirve fechas anteriores a 2020 | Baja |
| 5 | Si las imágenes traen geolocalización o son rasterizados planos | Media |

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
