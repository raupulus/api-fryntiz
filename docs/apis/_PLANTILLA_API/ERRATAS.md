# ⚠️ Erratas y trampas de <NOMBRE DE LA API>

> [!CAUTION]
> **Lectura obligatoria antes de implementar o modificar cualquier endpoint.**
> Agrupa todo lo que la documentación oficial dice mal, dice a medias o contradice el
> comportamiento real. Las del bloque A **rompen la integración sin lanzar ningún error**.

- **Fecha de la última verificación en vivo:** `<AAAA-MM-DD>`
- **Fuente verificada:** `<qué se probó y con qué credencial>`
- **Documentación auditada:** `<archivo en src/>`

Leyenda: 🟢 verificado · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

---

## 🔴 Bloque A — Erratas que rompen la integración sin avisar

<Fallos silenciosos: devuelven datos vacíos o falsos en vez de lanzar un error.
Codificación, códigos de estado dentro de un 200, datos obsoletos, campos que llegan nulos.
Son las más importantes: si el bloque está vacío, asegúrate de que es porque lo has verificado
y no porque no has mirado.>

---

## 🟠 Bloque B — La documentación miente sobre el formato

<Content-Type declarado vs real. Compresión. Estructura de los errores.>

---

## 🟡 Bloque C — Erratas de contenido en la documentación

<Erratas tipográficas en códigos o etiquetas, valores duplicados, parámetros con el mismo nombre
y distinto significado, enums ausentes, rutas con nombres contradictorios.>

---

## 🔵 Bloque D — Cosas que la API hace y la documentación no cuenta

<Funcionalidad indocumentada: multi-valor, endpoints que no requieren auth, campos útiles no
documentados, cabeceras ausentes.>

---

## 🔴 Bloque E — Erratas pendientes de confirmar

| # | Sospecha | Cómo verificarlo |
|---|---|---|

---

## Cómo añadir una errata

1. **Verifícala con una petición real.** Una sospecha no es una errata.
2. Colócala en su bloque (A rompe en silencio, B formato, C contenido, D indocumentado, E pendiente).
3. Incluye **la evidencia literal**: ruta, código HTTP, cabecera o fragmento de cuerpo.
4. Marca la fiabilidad y **actualiza la fecha** de la cabecera.
5. Si afecta a un endpoint concreto, añade un aviso en su módulo enlazando aquí.

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
