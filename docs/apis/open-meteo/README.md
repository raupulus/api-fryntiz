# 🌦️ Open-Meteo — Referencia técnica


API meteorológica global y abierta que agrega la salida de más de 30 modelos numéricos de una
quincena de servicios meteorológicos nacionales (ECMWF, DWD, NOAA, Météo-France, UK Met Office,
JMA, KMA…) en un único formato JSON.

- **Base URL:** **no hay una sola** — un subdominio por API ([mapa completo](00-fundamentos.md#mapa-de-endpoints-))
- **Especificación:** OpenAPI 3.1.0, pero **solo para 9 de los 16 endpoints**
- **Endpoints:** `16`, `GET` y también **`POST`** (indocumentado pero funcional)
- **Autenticación:** **ninguna** en el nivel gratuito · `apikey` en query para suscriptores → variable `OPEN_METEO_API_KEY`
- **Documentación oficial:** https://open-meteo.com/en/docs
- **Obtener credenciales:** no aplica — el uso de este proyecto es no comercial y el nivel gratuito no lleva credencial
- **Última verificación contra la API real:** **2026-09-01** (`16` de `16` endpoints · 190 peticiones en tres rondas ([desglose](ERRATAS.md)))

> [!TIP]
> Este directorio documenta **la API oficial de Open-Meteo**. Para saber **cómo la usa Api Raupulus**
> (servicios, comandos, modelos, caché), ver `docs/info/apis/`. Ver la
> [distinción entre ambos](../README.md#no-confundir-con-docsinfoapis).
>
> A fecha de 2026-08-31 **no hay ninguna integración de Open-Meteo en el código del proyecto**: esta
> documentación es previa a cualquier implementación.

---

> **Fuentes de este archivo:** `src/_MANIFEST.md` y el conjunto de `src/` + la verificación en vivo
> del 2026-08-31 registrada en cada módulo.

---

## 🚨 Normas de uso — OBLIGATORIAS

### 0. El uso en este proyecto es no comercial

Decidido el 2026-08-31: se usa el **nivel gratuito**, sin `apikey` y contra los hosts públicos. Eso
obliga a respetar los límites (600/min, 5.000/h, 10.000/día, 300.000/mes) y a mantener la atribución
CC BY 4.0. Detalle en
[`LIMITACIONES.md`](LIMITACIONES.md#uso-comercial--el-límite-que-no-es-técnico).

### 1. Nunca configurar nada a partir de la especificación sin verificarlo

Desviaciones **medidas** el 2026-08-31 entre lo declarado y lo real:

| Lo que dice la documentación | Lo que hace la API |
|---|---|
| Responde JSON | Con un modelo fuera de su cobertura devuelve **`200` con `nan` — JSON inválido** |
| `latitude` y `longitude` son obligatorios | Si faltan **los dos**: `200` con **cuerpo vacío** |
| ERA5 lleva 5 días de retraso | Medidos **6** — pero con `best_match` no hay retraso alguno |
| La Climate API llega a 2050 | `EC_Earth3P_HR` se queda en **2049**, y ninguna fuente lo dice |
| La spec enumera 22 variables `daily` y 50 modelos | Acepta variables y modelos **fuera de esos `enum`** |
| Las specs cubren la API | Cubren **9 de 16** endpoints |
| Solo hay peticiones `GET` | **`POST` funciona** en todas las rutas, con formulario o JSON |
| El esquema de respuesta no incluye `*_units` | Los devuelve **siempre** |
| El formulario oficial de la API estacional apunta a `/v1/forecast` | Esa ruta devuelve `200` con **todo a `null`** |

Antes de implementar o modificar cualquier endpoint:

1. **Haz la petición real** y mira el `Content-Type`, la codificación y la forma del cuerpo.
2. **Comprueba la frescura del contenido**, no solo el código HTTP.
3. **Anota lo observado** en el archivo de módulo correspondiente con marca 🟢 y fecha.

Un endpoint marcado 🔴 **no está verificado**: trátalo como desconocido, no como funcional.

### 2. Consulta [`ERRATAS.md`](ERRATAS.md) antes de tocar cualquier endpoint

### 3. Consulta [`LIMITACIONES.md`](LIMITACIONES.md) antes de diseñar cualquier automatismo

### 4. Nunca sondear los límites de uso a propósito

600 llamadas/min, 5.000/h, 10.000/día y 300.000/mes en el nivel gratuito. Provocar un `429` para ver
qué devuelve es abusar del servicio: se mide por observación, no forzándolo.

### 5. Toda afirmación va marcada y fechada

| Marca | Significado |
|---|---|
| 🟢 **Verificado** | Comprobado con petición real. Se indica fecha y parámetros usados. |
| 🔵 **Oficial** | Lo dice el proveedor pero **no lo hemos comprobado**. |
| 🟡 **Inferido** | Deducción nuestra, con el razonamiento explicado. |
| 🔴 **Sin verificar** | Pendiente. **No implementar sobre esto.** |
| ⚠️ **Errata** | La fuente oficial está mal. Detalle en [`ERRATAS.md`](ERRATAS.md). |

### 6. `src/` no se toca y no se lee de rutina

[`src/`](src/_MANIFEST.md) guarda las fuentes oficiales originales. **Nunca se editan.**

### 7. Las credenciales nunca se escriben aquí

Si algún día se contrata una suscripción, la clave irá en `.env` como `OPEN_METEO_API_KEY` y aquí se
citará solo el nombre de la variable. En el nivel gratuito no hay ninguna credencial que gestionar.

### 8. La atribución es obligatoria

Los datos son **CC BY 4.0**: toda vista que los muestre necesita un enlace visible a Open-Meteo.
Ver [`LIMITACIONES.md`](LIMITACIONES.md#condiciones-legales).

---

## 📑 Índice de archivos

**Empieza siempre por [`00-fundamentos.md`](00-fundamentos.md).**

| Archivo | Contenido | Endpoints |
|---|---|---|
| [`00-fundamentos.md`](00-fundamentos.md) | **Obligatorio.** Hosts, autenticación, errores, formatos, parámetros comunes | — |
| [`ERRATAS.md`](ERRATAS.md) | **Obligatorio.** Errores de la documentación oficial | — |
| [`LIMITACIONES.md`](LIMITACIONES.md) | **Obligatorio.** Uso comercial, cuotas, retrasos, licencia | — |
| [`01-prevision-meteorologica.md`](01-prevision-meteorologica.md) | Previsión horaria, diaria, cuarto-horaria y actual; variables, modelos, códigos WMO | `1` |
| [`02-historico-reanalisis.md`](02-historico-reanalisis.md) | Histórico ERA5/ERA5-Land/CERRA desde 1940 | `1` |
| [`03-archivo-de-predicciones.md`](03-archivo-de-predicciones.md) | Historical Forecast, Previous Runs y Single Runs | `3` |
| [`04-ensemble.md`](04-ensemble.md) | Previsión probabilística por miembros | `1` |
| [`05-estacional.md`](05-estacional.md) | SEAS5 y EC46 (7 meses reales, no los 9 anunciados), anomalías, EFI y SOT | `1` |
| [`06-clima-cmip6.md`](06-clima-cmip6.md) | Proyecciones climáticas 1950–2050 | `1` |
| [`07-marina.md`](07-marina.md) | Oleaje, mar de fondo y corrientes | `1` |
| [`08-calidad-del-aire.md`](08-calidad-del-aire.md) | Contaminantes, AQI europeo y estadounidense, polen | `1` |
| [`09-inundaciones.md`](09-inundaciones.md) | Caudal fluvial GloFAS | `1` |
| [`10-radiacion-satelite.md`](10-radiacion-satelite.md) | Irradiancia observada por satélite desde 1983 | `1` |
| [`11-geocodificacion-y-elevacion.md`](11-geocodificacion-y-elevacion.md) | Búsqueda de localidades, consulta por id y altitud | `3` |
| [`12-modelos-y-actualizaciones.md`](12-modelos-y-actualizaciones.md) | Catálogo de modelos y metadata API | `1` |

**Suma: 16 endpoints**, los mismos 16 del [mapa de fundamentos](00-fundamentos.md#mapa-de-endpoints-).

**Cobertura comprobada** 🟢: extraídas todas las URLs de API de las 39 páginas oficiales capturadas,
aparecen 17 rutas distintas — los 16 endpoints documentados más
`seasonal-api.open-meteo.com/v1/forecast`, que es la trampa descrita en
[`A3`](ERRATAS.md#a3--un-host-equivocado-devuelve-200-con-la-serie-entera-a-null-). Las páginas de
los 18 modelos individuales (incluida la de Google WeatherNext) **no son endpoints**: apuntan a
`/v1/forecast` o a `/v1/ensemble` con un valor distinto de `models`.

---

## 🧭 Qué leer según la tarea

| Necesito… | Leer |
|---|---|
| Empezar a integrar desde cero | `00-fundamentos.md` + `ERRATAS.md` + `LIMITACIONES.md` |
| Mostrar el tiempo de hoy o de los próximos días | `01-prevision-meteorologica.md` |
| Convertir una dirección o localidad en coordenadas | `11-geocodificacion-y-elevacion.md` |
| Datos de años anteriores | `02-historico-reanalisis.md` |
| Saber cada cuánto refrescar y cuándo lanzar un cron | `12-modelos-y-actualizaciones.md` + `LIMITACIONES.md` |
| Saber si podemos usarlo sin pagar | `LIMITACIONES.md` (primer apartado) |
| Depurar un error raro | `ERRATAS.md` + `00-fundamentos.md` |

---

## ⚡ Resumen ejecutivo — lo que hay que saber

1. **Uso no comercial, nivel gratuito, sin credencial.** Límites: 600/min, 5.000/h, 10.000/día.
2. **No hay autenticación ni clave que caduque** en el nivel gratuito: es HTTP `GET` y ya. Mucho más
   simple de operar que AEMET.
3. **Todo es UTF-8 real y JSON de verdad**, con estructura idéntica en las 16 rutas. La respuesta
   son **arrays paralelos** (`time[i]` ↔ `variable[i]`), no una lista de objetos.
4. **Las fechas ISO son hora local sin decirlo** (`"2026-09-01T00:00"`, sin `Z` ni offset). Con
   `APP_TIMEZONE=UTC` se desplaza la serie entera. Usar `timeformat=unixtime` o parsear indicando la
   zona.
5. **Los fallos son silenciosos.** Sin coordenadas: `200` y cuerpo vacío. Sin variables: `200` y
   solo metadatos. Host equivocado o coordenada sin oleaje: `200` y todo a `null`. Modelo fuera de
   su cobertura: `200` y un cuerpo con `nan` que **ni siquiera es JSON válido**. **Nunca basta con
   mirar el código HTTP, y ni siquiera basta con que el cuerpo parsee.**
6. **Cada API vive en su propio subdominio** y la especificación OpenAPI solo cubre 9 de los 16
   endpoints, con `enum` incompletos. La web es más fiable que la spec, y la petición real más que
   ninguna de las dos.
7. **Acepta `POST`** aunque no lo documente: es la vía para consultar cientos de coordenadas en una
   sola llamada (500 comprobadas) sin chocar con el límite de longitud de la URL.
8. **La atribución CC BY 4.0 es obligatoria** allí donde se muestren los datos.

> Creado: 2026-09-01 · Última revisión: 2026-09-01
