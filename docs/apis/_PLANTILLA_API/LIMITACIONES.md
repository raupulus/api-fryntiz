# 🚧 Limitaciones y condiciones de <NOMBRE DE LA API>

> [!CAUTION]
> **Lectura obligatoria antes de diseñar cualquier automatismo** (comando programado, job, cron,
> sincronización, widget que refresque solo). Descubrir estos límites en producción significa un
> servicio caído.

- **Fecha de la última verificación en vivo:** `<AAAA-MM-DD>`

Leyenda: 🟢 verificado · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

---

## Límites de uso

### El límite documentado 🔵

<Cita literal de la fuente oficial, con referencia.>

### El comportamiento real medido 🟢

<Tabla de pruebas y resultados. ¿Un contador global o cubos por endpoint?
¿Cuánto tarda en recuperarse? NUNCA sondees el umbral a propósito: mídelo por observación.>

### ¿Dice cuándo reintentar? 🔴

<¿Hay `Retry-After` o cabeceras `RateLimit-*`? Vuelca las cabeceras de un 429 real.>

### Consecuencias para el diseño

<Reglas concretas: espaciado, rotación, política de reintentos, TTL, si la web puede llamar
directamente o no.>

---

## Caducidad de credenciales

<¿Caducan? ¿Cuándo la actual? ¿Qué devuelve al caducar? ¿Cómo se renueva?
Si caduca, es trabajo de mantenimiento recurrente: dilo explícitamente.>

---

## Periodicidad real de actualización

<Cada cuánto cambia cada producto, **según la fuente oficial**, no según lo que apetezca refrescar.
Es la base del TTL de caché. Si la API expone metadatos con esta información, úsalos.>

| Producto | Periodicidad oficial | TTL 🟡 sugerido |
|---|---|---|

---

## Retrasos y disponibilidad de datos

| Limitación | Detalle |
|---|---|

---

## Qué NO ofrece esta API

<Para no perder tiempo buscándolo: funcionalidad ausente, datos de pago, formatos no soportados,
falta de paginación, falta de webhooks.>

---

## Volumen de las respuestas

<Tamaños MEDIDOS. Crítico antes de llamar a cualquier endpoint agregado ("todos", "todas").>

| Producto | Tamaño |
|---|---|

---

## Condiciones legales

<Atribución, licencia, uso comercial, límites de redistribución.
Si hay archivo dedicado, resume y enlaza.>

---

## Cómo añadir una limitación

1. Indica **la fuente**: documento concreto o medición propia con la evidencia.
2. Marca la fiabilidad y, si es medición, la fecha.
3. Añade siempre **la consecuencia para el diseño**. Una limitación sin consecuencia práctica es un
   dato inútil.
4. Actualiza la fecha de verificación de la cabecera.

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
