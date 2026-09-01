# Escribir metadatos de plataforma en las imágenes

> **Estado:** pendiente de verdad. No bloquea nada.

## Qué se quiere

Que las imágenes que sirve la plataforma —el original y sus miniaturas— lleven **metadatos propios**:
datos de la web y la autoría, más la orientación correcta.

Es lo contrario de lo que hace hoy `File::stripMetadata()`, y las dos cosas conviven en este orden:

1. **Limpiar** los metadatos que trae el archivo de origen (EXIF, GPS, IPTC, perfiles). Ya está
   hecho, ver [`docs/info/decisiones-tecnicas.md`](../info/decisiones-tecnicas.md) D3.
2. **Escribir** los nuestros. Es lo que falta.

El TODO vive en `App\Models\File::createThumbnails()` y **no se borra hasta que esto esté hecho**.

## Por qué no está hecho

No es un renglón. Hay dos obstáculos reales:

### 1. Nada de lo instalado sabe escribir EXIF

- **GD no escribe EXIF.** Lee dimensiones y poco más.
- **Intervention 4 no expone API para escribirlo.** Tiene `setExif()`, pero sirve para vaciar la
  colección de la instancia, no para persistir campos nuevos en el archivo.
- **Imagick sí sabría**, pero no está instalado en la máquina de desarrollo. Añadirlo cambia el
  driver de toda la cadena de imágenes, y eso arrastra la limpieza de metadatos y el redimensionado.

Queda una dependencia PHP pura del tipo **`lsolesen/pel`** (PHP Exif Library), que escribe EXIF en
JPEG sin extensiones nativas. Es la vía más barata, pero sólo cubre JPEG.

### 2. Las miniaturas se guardan en WebP

`createThumbnails()` convierte JPEG y PNG a WebP con `encode(new WebpEncoder(quality: 90, strip: true))`.
WebP **no lleva EXIF como JPEG**: los metadatos van en un chunk XMP, y el soporte de las librerías
PHP para escribir ahí es bastante pobre.

O sea que aunque se resuelva el punto 1 para el original, las miniaturas necesitan otra solución.

## Qué hay que decidir antes de implementarlo

1. **Qué se escribe exactamente.** Autor, URL del sitio, copyright, fecha. Conviene decidir el
   conjunto mínimo antes de elegir herramienta.
2. **Dónde se escribe.** Sólo en el original, o también en las miniaturas. Si es sólo en el
   original, el problema del WebP desaparece.
3. **Con qué.** `lsolesen/pel` (JPEG, sin extensiones) o instalar Imagick (todos los formatos, pero
   cambia el driver de toda la cadena).
4. **Qué pasa con la orientación.** Hoy se rota de verdad y se descarta el flag (D5). Si se escriben
   metadatos nuevos, decidir si se vuelve a declarar la orientación o se deja implícita en los
   píxeles, que es lo más robusto.

## Dónde tocar cuando se aborde

| Sitio | Qué |
|---|---|
| `App\Models\File::createThumbnails()` | El TODO. Escritura en cada miniatura |
| `App\Models\File::processStoredImage()` | Escritura en el original, justo después de `stripMetadata()` |
| `App\Models\File::stripMetadata()` | No se toca: limpiar sigue yendo primero |
| `docs/info/files.md` | Documentar qué metadatos se escriben |

## Qué NO hacer

**No quitar la limpieza de metadatos** pensando que escribir los propios ya la sustituye. Son dos
operaciones distintas: una borra lo que venía de la cámara de quien subió la foto —incluido el GPS—
y otra añade lo nuestro. El motivo completo está en D3.

> Creado: 2026-09-01 · Última revisión: 2026-09-01
