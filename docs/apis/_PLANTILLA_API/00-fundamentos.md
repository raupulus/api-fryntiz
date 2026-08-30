# 🧱 Fundamentos de <NOMBRE DE LA API>

> [!IMPORTANT]
> **Lectura obligatoria antes de cualquier otro archivo.**

Complementos obligatorios: [`ERRATAS.md`](ERRATAS.md) y [`LIMITACIONES.md`](LIMITACIONES.md).

Leyenda: 🟢 verificado (`<fecha>`) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `<lista de los ficheros de `src/` de los que sale este contenido, y si hubo verificación en vivo, con su fecha>`

---

## Datos básicos

| | |
|---|---|
| **Base URL** | `<url>` |
| **Prefijo de rutas** | `<prefijo>` |
| **Métodos** | `<…>` |
| **Autenticación** | `<mecanismo>` |
| **Codificación** | `<charset real, VERIFICADO>` |
| **Formato de respuesta** | `<real, no el declarado>` |

<Advierte de cualquier trampa en la construcción de URLs (dobles prefijos, caracteres no ASCII).>

---

## Autenticación

<Mecanismo. Si hay varias vías (cabecera / query), di cuál usar y por qué.
Nunca pongas la credencial: cita la variable de entorno.>

### Caducidad

<¿Caduca? ¿Cada cuánto? ¿Qué devuelve al caducar? Enlaza a LIMITACIONES.md.>

### Comportamiento sin credencial 🔴

<VERIFÍCALO. No es obvio: puede no ser un 401.>

---

## Flujo de una petición

<Si son varios saltos, dibújalo. Si es un salto, dilo explícitamente.>

---

## Codificación

<VERIFICA el charset real del cuerpo, no el declarado en la especificación.
Si no es UTF-8, avisa de que json_decode falla en silencio y explica qué hacer.>

---

## Validación de respuestas

**El código HTTP no basta.** Casos a verificar en toda API:

| Situación | HTTP | Cuerpo |
|---|---|---|
| Éxito | | |
| Recurso inexistente | | |
| Sin credencial | | |
| Ruta inexistente | | |
| Credencial inválida | | |
| Límite superado | | |

### Orden de comprobaciones

1. ¿El cuerpo está vacío?
2. ¿Es HTML en vez de JSON?
3. ¿Parsea tras convertir la codificación?
4. ¿El código de estado *del cuerpo* indica éxito?
5. ¿Están los campos necesarios?
6. **¿El contenido está fresco?**

---

## Formatos de fecha y parámetros

| Parámetro | Formato | Ejemplo |
|---|---|---|

### Precauciones

<Ceros a la izquierda, códigos que son cadenas, parámetros con el mismo nombre y distinto
significado, caracteres que hay que codificar en la URL.>

---

## Estrategia de consumo recomendada

<Diagrama o pasos. Reglas derivadas de los límites reales.
Regla general: la web nunca llama a la API de terceros en la petición del usuario.>

---

## Un ejemplo completo, de principio a fin 🔴

<Un `curl` real con su respuesta real, de punta a punta. Con la credencial como variable.
Sin esto, el archivo es teoría.>

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
