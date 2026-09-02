# Despliegue

Todo lo que hay que hacer **en la máquina** para poner esto en marcha vive aquí:
guías paso a paso y los sitios virtuales del servidor web.

La regla: en `docs/info/` está **cómo funciona** cada módulo; en `docs/deploys/`
está **cómo se despliega**. Si un documento de `docs/info` acaba explicando un
`systemd` o un `server {}` de nginx, está en el sitio equivocado.

| Fichero | Qué es |
|---|---|
| [`deploy-vps.md`](deploy-vps.md) | Guía completa del VPS: Docker o bare-metal, verificación, copias de seguridad, vuelta atrás y endurecimiento |
| [`websockets-reverb.md`](websockets-reverb.md) | Servidor de WebSockets: paquete, variables, demonio, certificado y cortafuegos |
| [`vhosts/`](vhosts/) | **Todos** los sitios virtuales, uno por servidor |

## Sitios virtuales (`vhosts/`)

Están los tres servidores a propósito: hoy el VPS va con **Apache**, y tener
nginx y Docker escritos y al día es lo que permite cambiar sin improvisar el
día que toque.

| Fichero | Servidor | Estado |
|---|---|---|
| [`vhosts/apache.conf`](vhosts/apache.conf) | Apache | **En uso en el VPS** |
| [`vhosts/apache-dev.conf`](vhosts/apache-dev.conf) | Apache | Desarrollo local, sin TLS ni HSTS |
| [`vhosts/nginx.conf`](vhosts/nginx.conf) | nginx | Listo para el cambio |
| [`vhosts/nginx-websocket.conf`](vhosts/nginx-websocket.conf) | nginx | Subdominio `ws.` de Reverb |
| [`vhosts/docker-nginx.conf`](vhosts/docker-nginx.conf) | nginx (contenedor) | Copia de `docker/nginx/default.conf` |

⚠️ **`docker/nginx/default.conf` es el que se construye en la imagen** —el
Dockerfile lo copia desde ahí—, y `vhosts/docker-nginx.conf` es su gemelo de
referencia. Si tocas uno, toca el otro. Los dos lo dicen en su cabecera.

### Por qué estaban repartidos y ya no

Hasta el 2026-09-02 había **cuatro** configuraciones con contenidos distintos:
`nginx.conf` y `apache.conf` en la raíz del repositorio, `docs/deploys/nginx.conf`
y `docker/nginx/default.conf`. Ninguna estaba declarada como la buena, y las dos
de la raíz —las más fáciles de copiar, por estar donde están— eran justo las que
**no tenían ninguna cabecera de seguridad**: desplegar con ellas dejaba el panel
de Filament clickjackeable. Además, el `apache.conf` de la raíz tenía un
`Redirect permanent` dentro del propio vhost `:443`, o sea un **bucle de
redirección**: por HTTPS no habría respondido nada.

Ahora no hay ningún `.conf` en la raíz, todos viven aquí y todos llevan sus
cabeceras. Y, por si acaso, **las cabeceras las pone también la aplicación**
(`App\Http\Middleware\SecurityHeaders`), así que viajan con el código y no
dependen de qué fichero se copió (auditoría AR-D01).

## Lo que más se olvida

| | |
|---|---|
| `REVERB_ALLOWED_ORIGINS` | **Nunca `*` en producción.** Es lo único que impide que cualquier web abra un socket contra el servidor |
| Reiniciar los procesos de larga vida | `api-fryntiz-reverb` y los workers de cola mantienen el código en memoria: un `git pull` no les llega |
| `pnpm build` después de tocar las `VITE_*` | Vite las lee **al compilar**, no en tiempo de ejecución |
| El 8080 no se abre en el cortafuegos | El demonio escucha en `127.0.0.1` y delante va nginx |

---

> Creado: 2026-08-30 · Última revisión: 2026-08-30
