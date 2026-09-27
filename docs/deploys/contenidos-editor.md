# Despliegue del editor de contenidos y de la API de contenidos

Lista para el día que se suba a producción el plan de contenidos del
2026-09-24 (fases F0–F9): editor multiformato, estados y publicación,
ficheros, permisos, borradores, bloqueo e historial, ficha por secciones,
pantalla de páginas y la API nueva. Se sigue **en este orden**. Cómo funciona
cada cosa está en [`docs/info/content.md`](../info/content.md); el contrato de
la API, en [`docs/info/api/v2/content.md`](../info/api/v2/content.md).

---

## 1. Antes de subir

- [ ] **Aviso a las webs que consumen la API de contenidos.** El contrato
      cambia (tabla «Cambios de contrato del 2026-09-27» del contrato): `type` y
      `status` compactos, el detalle trae el índice de páginas y la primera
      página, la imagen de los relacionados cambia de forma y las páginas salen
      en su formato si no se pide `?format=html`.
- [ ] **Imagick con HEIC.** Las fotos del móvil (HEIC/HEIF) y AVIF se abren con
      Imagick; sin él, el editor responde «Este servidor no puede leer fotos
      HEIC».

      ```bash
      sudo apt install -y php8.4-imagick libheif1
      php -r 'var_dump(Imagick::queryFormats("HEI*"));'   # tiene que listar HEIC y HEIF
      sudo systemctl reload php8.4-fpm
      ```

- [ ] **Tamaño de las peticiones.** PHP con `upload_max_filesize` y
      `post_max_size` de 50 MB (ya estaba). El servidor web tiene que dejar
      pasar lo mismo: en nginx `client_max_body_size 50M` (ya está en
      [`vhosts/nginx.conf`](vhosts/nginx.conf)); en Apache, `LimitRequestBody`
      sin poner o de 50 MB o más. Livewire acepta formularios de hasta 8 MB
      (`config/livewire.php`), que caben de sobra.

## 2. Subida

```bash
cd /var/www/api-fryntiz
sudo -u www-data git pull
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data php artisan migrate --force
```

`migrate` aplica cinco migraciones, todas de añadir (no borran ni cambian
datos):

| Migración | Qué hace |
|---|---|
| `2026_09_26_000001_add_auto_contributor_to_platform_user_table` | `platform_user.auto_contributor` (colaborador automático por plataforma) |
| `2026_09_26_000002_create_content_page_drafts_table` | Borradores de las páginas |
| `2026_09_26_000003_create_content_page_versions_table` | Historial de versiones |
| `2026_09_26_000004_add_lock_to_content_pages_table` | Bloqueo de cada página mientras se edita |
| `2026_09_26_000005_add_unused_since_to_content_files_table` | Marca de los ficheros que ya no se usan |

`public/build` va en git: no hace falta compilar en el servidor.

Después, **limpiar y volver a cachear** (hay rutas nuevas: con la caché de
rutas vieja responden 404) y reiniciar lo que tiene el código en memoria. Un
comando por línea:

```bash
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan event:cache
sudo -u www-data php artisan filament:optimize
sudo -u www-data php artisan queue:restart
sudo systemctl reload php8.4-fpm
```

## 3. Tareas programadas

- [ ] El cron del sistema ejecuta el scheduler cada minuto:

      ```bash
      sudo crontab -u www-data -l   # * * * * * cd /var/www/api-fryntiz && php artisan schedule:run …
      ```

- [ ] `php artisan schedule:list` enseña estas tres:

      | Tarea | Cuándo |
      |---|---|
      | `content:publish` | Cada 5 minutos: publica los programados |
      | `content:prune-drafts-and-versions` | Diaria, 04:00 (Madrid) |
      | `content:purge-unused-files` | Diaria, 04:15 (Madrid) |

## 4. Comprobación

Con `curl`, comparando con una ruta que ya existía (si la vieja responde y la
nueva da 404, es la caché de rutas, no el código):

```bash
API=https://api.raupulus.dev/api/v2/platforms
curl -s -o /dev/null -w "%{http_code}\n" "$API"                         # la de siempre: 200
curl -s "$API/portfolio" | head -c 600; echo                           # ficha: social_networks, author, contents…
curl -s "$API/portfolio" | grep -c "_token"                            # tiene que dar 0
curl -s "$API/portfolio/contents/highlights" | head -c 300; echo
curl -s "$API/portfolio/contents?per_page=1" | head -c 300; echo       # coge el slug de uno publicado
curl -s "$API/portfolio/contents/<slug>" | head -c 600; echo           # pages (índice) y first_page
```

Huella y 304 (tiene que responder `304 Not Modified` sin cuerpo):

```bash
E=$(curl -s -D - -o /dev/null "$API/portfolio" | grep -i '^etag' | cut -d' ' -f2 | tr -d '\r')
curl -s -o /dev/null -w "%{http_code}\n" -H "If-None-Match: $E" "$API/portfolio"
```

Y en el panel: abrir un contenido, su sección «Páginas», guardar con
Ctrl/Cmd+S y subir una foto.

⚠️ Los contenidos que vienen de la v1 no tienen estado: **no salen en la API
hasta publicarlos** desde el panel (acción masiva «Publicar» del listado).

## 5. El dominio de las imágenes (G1)

Las páginas guardan las imágenes con `https://api.fryntiz.dev`. Se cambian
a `https://api.raupulus.dev` con esta consulta, **después** de la subida.

En el JSON de Editor.js (`content_page_raw`) las barras van escapadas
(`https:\/\/api.fryntiz.dev`); en el HTML servido (`content_pages`), no. Por
eso la primera sentencia cambia las dos formas, y se busca con `strpos` y no
con `LIKE`, donde `\` es el carácter de escape.

```sql
-- Antes: filas con el dominio viejo (en la copia local: 12 y 12)
select
  (select count(*) from content_page_raw where strpos(content, 'api.fryntiz.dev') > 0) as raws,
  (select count(*) from content_pages   where strpos(content, 'api.fryntiz.dev') > 0) as paginas;

begin;

update content_page_raw
   set content = replace(replace(content, 'https:\/\/api.fryntiz.dev', 'https:\/\/api.raupulus.dev'),
                         'https://api.fryntiz.dev', 'https://api.raupulus.dev')
 where strpos(content, 'api.fryntiz.dev') > 0;

update content_pages
   set content = replace(content, 'https://api.fryntiz.dev', 'https://api.raupulus.dev')
 where strpos(content, 'api.fryntiz.dev') > 0;

-- Después: tiene que dar 0 y 0. Si no, `rollback;` en vez de `commit;`.
select
  (select count(*) from content_page_raw where strpos(content, 'api.fryntiz.dev') > 0),
  (select count(*) from content_pages   where strpos(content, 'api.fryntiz.dev') > 0);

commit;
```

- Probada en la copia local (dentro de una transacción, deshecha después):
  12 y 12 filas antes (284 y 213 apariciones), 0 y 0 después; el JSON de
  todas las páginas Editor.js sigue siendo válido, y el HTML que se genera
  desde el JSON nuevo es el servido antes con el dominio cambiado (19 de 19
  páginas). Una muestra de 24 imágenes responde 200 en el dominio nuevo.
- La API guarda sus respuestas hasta 2 horas y la consulta no pasa por los
  modelos: si se lanza en otro momento que el paso 2, después
  `sudo -u www-data php artisan cache:clear`.
- No toca el historial de versiones ni los borradores (recuperar una versión
  vieja traería el dominio viejo, que sigue respondiendo), ni el enlace
  `content_metadata.web` del proyecto de la propia API (`https://api.fryntiz.dev`),
  que es un enlace a la v1 y no una imagen.

## 6. Si algo va mal

Las cinco migraciones son de añadir: el código anterior funciona con ellas
puestas. Volver al commit anterior (`git checkout <commit>` y los comandos del
paso 2 sin `migrate`) basta. Si hiciera falta quitarlas, y siguen siendo las
últimas aplicadas (`php artisan migrate:status`):
`php artisan migrate:rollback --step=5`. Borra los borradores, el historial y
los bloqueos que se hubieran creado desde la subida.

---

> Creado: 2026-09-27 · Última revisión: 2026-09-27
