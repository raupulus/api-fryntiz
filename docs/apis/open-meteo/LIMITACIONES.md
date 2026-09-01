# 🚧 Limitaciones y condiciones de Open-Meteo


> [!CAUTION]
> **Lectura obligatoria antes de diseñar cualquier automatismo** (comando programado, job, cron,
> sincronización, widget que refresque solo). Descubrir estos límites en producción significa un
> servicio caído — o, en el caso de la licencia, un incumplimiento de las condiciones de uso.

- **Fecha de la última verificación en vivo:** `2026-09-01`

Leyenda: 🟢 verificado · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

---

## Uso comercial — el límite que no es técnico

> [!NOTE]
> El nivel gratuito está restringido a **uso no comercial**. **En este proyecto el uso es no
> comercial**, así que aplica el nivel gratuito. Se documenta aquí porque no hay control técnico
> alguno: la API responde igual, y el encaje es contractual.

Texto oficial 🔵 (`src/web-texto/terms.txt`, descargado el 2026-08-31):

> «You may only use the free API services for non-commercial purposes.»

La propia página enumera qué cuenta como cada cosa:

| Cuenta como **no comercial** 🔵 | Cuenta como **comercial** 🔵 |
|---|---|
| Webs o apps privadas o sin ánimo de lucro, sin suscripciones ni publicidad | Webs o apps **con suscripciones o publicidad** |
| Domótica personal | Integrarlo en **productos comerciales o acciones promocionales** |
| Investigación pública en instituciones públicas | Investigación no divulgada en entidades comerciales |
| Contenido educativo | |

Y sobre el nivel gratuito, la página de precios añade 🔵: «Use the free tier for evaluation and
prototyping».

**Decisión tomada (2026-08-31): el uso en este proyecto es NO COMERCIAL.** Se usa el nivel gratuito,
sin `apikey` y contra los hosts públicos (sin prefijo `customer-`).

Lo que eso implica en la práctica: hay que respetar los límites de la tabla siguiente (600/min,
5.000/h, 10.000/día, 300.000/mes) y mantener la atribución CC BY 4.0. Si en el futuro cambiara el
encaje del proyecto, habría que contratar un plan y cambiar host y `apikey`; el resto de la sintaxis
es idéntica.

También hay reserva expresa de bloqueo 🔵: «We reserve the right to block applications and IP
addresses that misuse our service without prior notice.» — sin aviso previo.

---

## Límites de uso

### Los límites documentados 🔵

Idénticos en `/en/terms` y `/en/pricing` (2026-08-31):

| Ventana | Nivel gratuito | Standard | Professional | Enterprise |
|---|---|---|---|---|
| Por minuto | **600** llamadas | Sin límite | Sin límite | Sin límite |
| Por hora | **5.000** llamadas | Sin límite | Sin límite | Sin límite |
| Por día | **10.000** llamadas | Sin límite | Sin límite | Sin límite |
| Por mes | **300.000** llamadas | 1 M | 5 M | > 50 M |

Las llamadas a la **metadata API no cuentan** para los límites diarios ni mensuales 🔵.

### El comportamiento real medido 🟢

| Qué se midió | Resultado |
|---|---|
| 190 peticiones espaciadas 3–5 s, sin credencial, 2026-08-31 y 2026-09-01 (tres rondas) | Todas respondidas; ningún `429` |
| Cabeceras de cuota (`X-RateLimit-*`, `RateLimit-*`) | **No existen** en ninguna de las respuestas |
| Cabecera `Retry-After` | No observada (no hubo ningún `429`) |
| Latencia típica | 0,19 – 0,31 s por petición |

> [!NOTE]
> **El umbral no se ha sondeado a propósito y no debe sondearse.** Provocar un `429` para ver qué
> devuelve es abusar del servicio. Lo que sí hay que hacer es registrar el cuerpo y **todas** las
> cabeceras la primera vez que aparezca uno en uso normal, y anotarlo aquí.

### ¿Dice cuándo reintentar? 🔴

Desconocido. Sin cabeceras de cuota y sin un `429` observado, no hay forma de saber si la API indica
el momento de reintento. **Hay que asumir que no**, igual que en AEMET.

### Consecuencias para el diseño 🟡

1. **La web nunca llama a Open-Meteo dentro de la petición del usuario.** Un pico de tráfico se
   traduciría directamente en un pico de llamadas: 600/min se agotan con 10 visitas por segundo.
2. **Un comando programado escribe en caché o en base de datos; las vistas leen de ahí.**
3. **Agrupar localizaciones en una sola petición.** Multi-localización (`latitude=a,b,c`) es una
   única llamada para N puntos — la palanca más eficaz para no acercarse a la cuota. Cuidado con la
   trampa de `location_id` ([`ERRATAS.md` A2](ERRATAS.md#a2--location_id-no-existe-en-el-primer-elemento-de-una-respuesta-multi-localización-)).
   Para muchos puntos, **`POST` con JSON** evita el límite de longitud de la URL: 500 coordenadas
   comprobadas en una llamada ([`ERRATAS.md` D10](ERRATAS.md#d10--la-api-acepta-post-y-eso-no-está-documentado-en-ninguna-parte-)).
4. **Espaciar y no reintentar en bucle.** Sin `Retry-After`, un reintento inmediato tras un error
   solo empeora la situación. Retroceso exponencial con un máximo de intentos.
5. **El TTL de caché sale de la periodicidad real del modelo**, no de lo que apetezca refrescar
   (tabla más abajo).

---

## Caducidad de credenciales

En el nivel gratuito **no hay credencial**, así que no hay nada que caducar ni que rotar 🟢. Es una
ventaja de mantenimiento frente a AEMET, cuya clave sí caduca.

Para las suscripciones de pago la documentación no menciona caducidad de la `apikey` 🔴. Sí menciona
que el impago corta el acceso a los 14 días 🔵 y que la baja surte efecto al final del periodo de
facturación en curso 🔵.

---

## Periodicidad real de actualización

Base para el TTL de caché. Frecuencias oficiales 🔵 de `src/web-texto/forecast.txt` y
`src/web-texto/historical-weather.txt`:

| Producto | Periodicidad | Medido el 2026-08-31 🟢 | TTL 🟡 sugerido |
|---|---|---|---|
| Previsión — ICON D2 (2 km, Centroeuropa) | Cada 3 h 🔵 | Pasada 03:00 → disponible 04:24 | 1 h |
| Previsión — ICON EU | Cada 3 h 🟢 | Pasada 03:00 → disponible 05:53 | 1 h |
| Previsión — ICON global | Cada 6 h 🟢 | Pasada 00:00 → disponible 03:45 | 2 h |
| Previsión — AROME France HD | Cada 3 h 🟢 | Pasada 03:00 → disponible 05:39 | 1 h |
| Previsión — ARPEGE Europa | Cada 6 h 🟢 | Pasada 00:00 → disponible 03:44 | 2 h |
| Previsión — GFS (`ncep_gfs013`) | Cada 6 h 🟢 | Pasada 00:00 → disponible 05:43 | 2 h |
| Previsión — UKMO global 10 km | Cada 6 h 🟢 | Pasada 18:00 → disponible 01:07 | 2 h |
| Previsión — ECMWF IFS 0,25° | Cada 6 h 🟢 | Pasada 18:00 → disponible 01:14 | 2 h |
| Previsión — condiciones actuales (`current`) | Datos de modelo cada 15 min 🔵 | — | 15 min |
| Calidad del aire (`cams_europe`) | **Diaria** 🟢 | Pasada 00:00 → disponible 11:32 | 6 h |
| Inundaciones (`glofas_forecast_v4`) | **Diaria** 🟢 | Pasada 00:00 → disponible 12:49 | 12 h |
| Marina | Cada 6 h 🔵 | — | 2 h |
| Histórico ERA5 | Diario 🟢 | Último dato: **hace 6 días** | 24 h |
| Histórico ERA5-Land | Diario 🟢 | Último dato: **hace 6 días** | 24 h |
| Histórico ECMWF IFS (vía `best_match`) | Cada 6 h, sin retraso 🟢 | Dato del mismo día de la consulta | 6 h |
| Histórico CERRA | **No se actualiza** 🟢: cubre ~1985–2021 pese a que la web lo anuncia como «2024 → hoy» | Último dato: 2021 | Permanente |
| Clima CMIP6 (1950–2050) | Dataset estático 🔵 | — | Semanas |

> [!IMPORTANT]
> **Los modelos globales tardan entre 5 y 7 horas en estar disponibles** desde su hora de pasada.
> Un cron «a las 00:15 porque la pasada es de las 00:00» leería datos de seis horas antes. La forma
> correcta de programarlo es consultar `meta.json` del modelo y usar `last_run_availability_time`
> + 10 minutos. Tabla completa en
> [`12-modelos-y-actualizaciones.md`](12-modelos-y-actualizaciones.md#tiempos-de-disponibilidad-medidos-).

### El retraso real de disponibilidad 🟢

Medido el 2026-08-31 con `https://api.open-meteo.com/data/dwd_icon/static/meta.json`:

| Campo | Valor | Traducido |
|---|---|---|
| `last_run_initialisation_time` | `1788134400` | 2026-08-31 **00:00 UTC** |
| `last_run_modification_time` | `1788147417` | 2026-08-31 03:36 UTC |
| `last_run_availability_time` | `1788147916` | 2026-08-31 **03:45 UTC** |
| `update_interval_seconds` | `21600` | Cada 6 h |
| `temporal_resolution_seconds` | `3600` | Datos horarios |

Es decir: la pasada de las 00:00 UTC estuvo disponible en la API **3 h 45 min después**. Una sola
medición, de un solo modelo 🟢 — no vale para los demás.

> [!IMPORTANT]
> A eso hay que sumarle **10 minutos más**. Aviso oficial 🔵: los servidores de Open-Meteo son
> redundantes y «eventualmente consistentes»; la web recomienda esperar 10 minutos tras la hora de
> disponibilidad para que todos los servidores tengan el dato. Programar un comando justo a la hora
> del run es garantía de leer datos viejos.

Nota oficial adicional 🔵: **los servidores gratuitos y los de pago se actualizan en momentos
ligeramente distintos**.

---

## Retrasos y disponibilidad de datos

| Limitación | Detalle | Fiabilidad |
|---|---|---|
| ERA5 con `models=era5` | **6 días** de retraso medido el 2026-08-31 (la documentación dice 5). Con `best_match` no hay retraso, porque usa IFS | 🟢 |
| CERRA | **Termina en 2021**; la web dice «2024 to present» y es falso ([`C9`](ERRATAS.md#c9--la-web-describe-cerra-como-un-producto-en-tiempo-real-y-termina-en-2021-)) | 🟢 |
| Consistencia eventual entre servidores | Esperar 10 min tras `last_run_availability_time` | 🔵 |
| Archivo de predicciones (Historical Forecast) | La web dice ~2021; **hay datos desde 2017** | 🟢 |
| Previous Runs | La web dice enero de 2024; **hay datos en 2023**, y la serie tiene **huecos de días sueltos** | 🟢 |
| Single Runs | Desde el 2 de abril de 2026 (ECMWF IFS HRES, desde marzo de 2024) | 🔵 |
| Radiación por satélite | SARAH3 desde 1983 · Himawari-9 desde 2015 · MTG desde febrero de 2026 | 🔵 |
| Inundaciones (GloFAS) | Previsión de 366 días aceptada, **pero solo ~185 con dato** | 🟢 |
| Datos 15-minutales nativos | Solo Centroeuropa y Norteamérica; **en el resto se interpolan** desde los horarios | 🔵 |
| Retrasos de modelo > 20 min | Ocurren: la página de estado los marca en amarillo, y en rojo si se pierden varias pasadas. «Minor delays are fairly common» | 🔵 |

**Consecuencia para el diseño:** para «lo que pasó ayer» hay que usar la Forecast API con
`past_days`, no la Historical Weather API.

---

## Qué NO ofrece esta API

- **No hay webhooks ni notificaciones push.** Todo es petición activa.
- **No hay paginación**: cada petición devuelve la serie completa que se le pida. El control de
  tamaño se hace con `forecast_days`, `start_date`/`end_date` y el número de variables.
- **Máximo de 100 coordenadas por petición** en `/v1/elevation`, y de 100 resultados en la
  geocodificación 🟢: pasarse devuelve un `400` con mensaje claro.
- **No hay avisos ni alertas meteorológicas oficiales** (el equivalente a los avisos de AEMET). Open-Meteo
  sirve salida de modelos numéricos, no productos de aviso de un servicio nacional.
- **No hay observaciones de estaciones** — todo es salida de modelo o reanálisis.
- **No hay SDK obligatorio** ni autenticación en el nivel gratuito: HTTP `GET` y ya.
- **No hay endpoint de estado dentro de la API**: el estado del servicio vive fuera, en
  `status.open-meteo.com` 🔵.
- **No hay un catálogo de localidades propio**: la geocodificación se apoya en GeoNames.

---

## Volumen de las respuestas 🟢

Tamaños **medidos** el 2026-08-31, sin comprimir salvo donde se indica:

| Petición | Tamaño |
|---|---|
| `/v1/forecast`, 1 variable horaria, 1 día | 841 B |
| `/v1/forecast`, 1 variable horaria, 16 días, **comprimido** (`deflate`) | 1.771 B |
| `/v1/forecast`, 1 variable diaria, 1 día, 1 localización | 325 B |
| `/v1/forecast`, 1 variable diaria, 1 día, 3 localizaciones | 623 B |
| `/v1/air-quality`, 2 variables horarias, 1 día | 911 B |
| `/v1/ensemble`, 1 variable, 1 día, `icon_seamless` (**20+ miembros**) | 7.857 B |
| `/v1/seasonal`, 1 variable diaria, 2 días (**51 miembros**) | 4.209 B |
| `/v1/elevation`, 1 coordenada | 21 B |
| `/v1/archive`, 1 variable horaria × **30 años** | **6,23 MB** · 950 KB con compresión · 0,69 s |
| `/v1/archive`, 1 variable **diaria** × 30 años | 196 KB |
| `/v1/forecast` por `POST`, 1 variable diaria × **500 coordenadas** | 153 KB · 0,38 s |
| `/v1/search` (geocodificación), 2 resultados | 912 B |
| `/v1/search`, 1 resultado en `format=protobuf` | 319 B (frente a 912 B del mismo en JSON) |
| `/v1/flood` con `ensemble=true`, 2 días (**51 miembros**) | 51 series |

> [!TIP]
> El multiplicador peligroso no es el número de días: son **los miembros de ensemble** y **el número
> de variables**. Una petición de ensemble con 10 variables y 15 días es tres órdenes de magnitud
> mayor que la equivalente determinista.

Estimación previa a cualquier petición grande: `nº de valores ≈ horas × variables × localizaciones ×
miembros`. Y **pedir siempre compresión** en series largas: en la prueba de 30 años bajó de 6,23 MB a
950 KB sin coste de tiempo.

---

## Condiciones legales

| Aspecto | Condición |
|---|---|
| **Licencia de los datos** | **CC BY 4.0** (Attribution 4.0 International) 🔵 |
| **Licencia del software** | AGPLv3 o posterior (solo relevante si se auto-aloja) 🔵 |
| **Atribución obligatoria** | Sí, **junto a cada lugar donde se muestren los datos** 🔵 |
| **Uso comercial del nivel gratuito** | **No permitido** — ver el primer apartado |
| **Ley aplicable** | Suiza 🔵 |
| **Garantías** | Ninguna. Servicio «as is», sin garantía de exactitud ni de disponibilidad 🔵 |

Forma de atribución que pide la página oficial 🔵:

```html
<a href="https://open-meteo.com/">Weather data by Open-Meteo.com</a>
```

Los datos de origen tienen además sus propias licencias (DWD, ECMWF, NOAA, Météo-France, Copernicus,
GeoNames…), casi todas CC-BY, y **UK Met Office es CC-BY-SA** 🔵. La API de elevación exige citar
también al programa Copernicus 🔵. La lista completa está en `src/web-texto/licence.txt`.

**Consecuencia para el diseño:** cualquier vista que muestre datos de Open-Meteo necesita el enlace
de atribución visible. No es opcional ni negociable con la licencia elegida.

---

## Cómo añadir una limitación

1. Indica **la fuente**: documento concreto de `src/` o medición propia con la evidencia.
2. Marca la fiabilidad y, si es medición, la fecha.
3. Añade siempre **la consecuencia para el diseño**. Una limitación sin consecuencia práctica es un
   dato inútil.
4. Actualiza la fecha de verificación de la cabecera.

> Creado: 2026-09-01 · Última revisión: 2026-09-01
