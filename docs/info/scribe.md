# Documentación de la API (Scribe)

La documentación interactiva de la API vive en `/docs`, **detrás de login**.

Lo que hay que saber en una línea: **se genera en local y se sube ya compilada
al repositorio**. En el servidor no se genera nada.

---

## 1. Por qué no se genera en el servidor

Scribe está en `require-dev`, y el servidor instala con
`composer install --no-dev`: allí el paquete no existe. Generar la
documentación en producción obligaría a instalar dependencias de desarrollo en
una máquina pública, y a ejecutar los *response calls* de Scribe —que llaman a
los endpoints de verdad— contra la base de datos real. Para una documentación
que apenas cambia entre versiones, no compensa.

Lo que se despliega es el resultado, que es HTML y no necesita el paquete:

| Qué | Dónde | En git |
|---|---|---|
| Página de la documentación | `resources/views/scribe/index.blade.php` | sí |
| CSS y JS del tema | `public/vendor/scribe/` | sí |
| Especificación OpenAPI | `storage/app/scribe/openapi.yaml` | sí |
| Colección de Postman | `storage/app/scribe/collection.json` | sí |
| Textos propios (intro, auth, endpoints a mano) | `.scribe/` | sí |

Las rutas las registra la aplicación en `routes/web.php`, no Scribe
(`laravel.add_routes => false` en `config/scribe.php`). Son tres, todas con
middleware `auth`:

- `/docs` — la página
- `/docs.openapi` — la especificación OpenAPI
- `/docs.postman` — la colección de Postman

`config/scribe.php` se desactiva solo cuando el paquete no está instalado: si no
lo hiciera, `php artisan config:cache` reventaría en el servidor al no encontrar
las clases de Scribe, y con él el despliegue entero.

---

## 2. La URL que se publica

`base_url` **no** sale de `APP_URL`, sino de `SCRIBE_BASE_URL`
(`config/scribe.php`), con `https://api.raupulus.dev` por defecto.

El motivo es el problema clásico de este flujo: al generar en local con
`APP_URL=http://localhost:8000`, la documentación se subía anunciando
`http://localhost:8000` como URL de la API, y el botón «Try it out» apuntaba al
portátil de quien la generó. Con una variable propia se genera desde local
apuntando ya al dominio público, sin tener que cambiar `APP_URL` antes y
volver a dejarla como estaba después.

Si algún día hay que publicar la documentación en otro dominio, se pone
`SCRIBE_BASE_URL` en el `.env` **de la máquina donde se genera**. En el servidor
esa variable no pinta nada.

---

## 3. Cómo se actualiza

En local, tras cambiar endpoints:

```bash
php artisan scribe:generate
```

Y se commitean los ficheros de la tabla de arriba. Nada más: el despliegue no
ejecuta ningún comando de Scribe.

Antes de commitear conviene comprobar que no se ha colado una URL local:

```bash
php artisan test --filter=ScribeDocsTest
```

Esa prueba verifica que la documentación se sirve, que sigue siendo privada y
que la URL que anuncia es la pública.

> Los *response calls* se ejecutan sólo sobre `GET` y dentro de una transacción
> (`database_connections_to_transact` en `config/scribe.php`), así que generar
> la documentación no deja datos en la base de datos local.
