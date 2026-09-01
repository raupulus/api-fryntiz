# <ICONO> <NOMBRE DE LA API> — Referencia técnica

<Una frase: qué es esta API y de quién.>

- **Base URL:** `<url>`
- **Especificación:** `<tipo y versión, o "no publica especificación">`
- **Endpoints:** `<N>`, métodos `<GET/POST…>`
- **Autenticación:** `<mecanismo>` → variable `<NOMBRE_EN_ENV>`
- **Documentación oficial:** <url>
- **Obtener credenciales:** <url>
- **Última verificación contra la API real:** **<AAAA-MM-DD>** (`<N>` de `<TOTAL>` endpoints)

> [!TIP]
> Este directorio documenta **la API oficial de \<PROVEEDOR\>**. Para saber **cómo la usa Api Raupulus**
> (servicios, comandos, modelos, caché), ver `docs/info/apis/`. Ver la
> [distinción entre ambos](../README.md#no-confundir-con-docsinfoapis).

---

> **Fuentes de este archivo:** `<ficheros de `src/` que respaldan este contenido + fecha de la
> verificación en vivo si la hubo>`

---

## 🚨 Normas de uso — OBLIGATORIAS

### 1. Nunca configurar nada a partir de la especificación sin verificarlo

<Si esta API tiene desviaciones medidas entre lo declarado y lo real, resúmelas aquí con cifras.
Si aún no se ha verificado nada, dilo explícitamente.>

Antes de implementar o modificar cualquier endpoint:

1. **Haz la petición real** y mira el `Content-Type`, la codificación y la forma del cuerpo.
2. **Comprueba la frescura del contenido**, no solo el código HTTP.
3. **Anota lo observado** en el archivo de módulo correspondiente con marca 🟢 y fecha.

Un endpoint marcado 🔴 **no está verificado**: trátalo como desconocido, no como funcional.

### 2. Consulta [`ERRATAS.md`](ERRATAS.md) antes de tocar cualquier endpoint

### 3. Consulta [`LIMITACIONES.md`](LIMITACIONES.md) antes de diseñar cualquier automatismo

### 4. Toda afirmación va marcada y fechada

| Marca | Significado |
|---|---|
| 🟢 **Verificado** | Comprobado con petición real. Se indica fecha y parámetros usados. |
| 🔵 **Oficial** | Lo dice el proveedor pero **no lo hemos comprobado**. |
| 🟡 **Inferido** | Deducción nuestra, con el razonamiento explicado. |
| 🔴 **Sin verificar** | Pendiente. **No implementar sobre esto.** |
| ⚠️ **Errata** | La fuente oficial está mal. Detalle en [`ERRATAS.md`](ERRATAS.md). |

### 5. `src/` no se toca y no se lee de rutina

[`src/`](src/_MANIFEST.md) guarda las fuentes oficiales originales. **Nunca se editan.**

### 6. Las credenciales nunca se escriben aquí

Van en `.env` (`<NOMBRE_EN_ENV>`). En documentación se cita la variable, jamás el valor.
<Si la credencial caduca, dilo y enlaza a LIMITACIONES.md.>

---

## 📑 Índice de archivos

**Empieza siempre por [`00-fundamentos.md`](00-fundamentos.md).**

| Archivo | Contenido | Endpoints |
|---|---|---|
| [`00-fundamentos.md`](00-fundamentos.md) | **Obligatorio.** Autenticación, flujo, errores, formatos | — |
| [`ERRATAS.md`](ERRATAS.md) | **Obligatorio.** Errores de la documentación oficial | — |
| [`LIMITACIONES.md`](LIMITACIONES.md) | **Obligatorio.** Límites, cuotas, caducidades | — |
| `01-<dominio>.md` | <descripción> | `<n>` |
| … | | |

<Comprueba que los endpoints suman el total de la API.>

---

## 🧭 Qué leer según la tarea

| Necesito… | Leer |
|---|---|
| Empezar a integrar desde cero | `00-fundamentos.md` + `ERRATAS.md` + `LIMITACIONES.md` |
| <tarea frecuente> | <archivos> |
| Depurar un error raro | `ERRATAS.md` + `00-fundamentos.md` |

---

## ⚡ Resumen ejecutivo — lo que hay que saber

<De 3 a 5 puntos. Lo que evitaría un fallo a quien vaya a integrar esto por primera vez.
Si no hay nada verificado todavía, escribe eso y no rellenes con generalidades.>

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
