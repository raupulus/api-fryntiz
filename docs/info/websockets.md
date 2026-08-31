# WebSockets — Laravel Reverb

Las estaciones meteorológicas suben lecturas cada pocos segundos. Sin
WebSockets, una web que quiera enseñarlas «en vivo» tiene que preguntar en
bucle: con ocho webs y una lectura cada cinco segundos, casi todas esas
peticiones devuelven lo mismo que la anterior. Esto invierte el sentido: la API
avisa cuando hay algo nuevo.

## Estado

**Implementado e instalado en el código, apagado por defecto.**
`laravel/reverb`, `laravel-echo` y `pusher-js` ya están en `composer.json` y
`package.json`. `BROADCAST_CONNECTION=null` es el valor de fábrica: no se
emite nada hasta que se pone `reverb` **y** el demonio está corriendo.
Encenderlo es una decisión de despliegue, no de instalación.

---

## 1. Qué se emite

Un evento, `App\Events\WeatherStation\ReadingsReceived`, **una vez por
petición** de subida.

| | |
|---|---|
| Canal | `weather-station.{id}` — público |
| Nombre del evento | `readings.received` |
| Se emite desde | `SensorReadingController::store()` y `::storeReadings()`, después de la transacción |

Carga:

```json
{
  "station_id": 3,
  "sensors": {
    "temperatures": [{ "value": 21.4, "hardware_device_id": 3, "created_at": "…" }],
    "humidities":   [{ "value": 63.2, "hardware_device_id": 3, "created_at": "…" }]
  },
  "at": "2026-08-30T02:41:07.000000Z"
}
```

Lleva **lo que se acaba de insertar**, no la foto completa de la estación. Se
podría volver a consultar todo y mandar el mismo JSON que devuelve
`GET /weather-stations/{id}`, pero eso son doce consultas por escritura y la
estación escribe cada pocos segundos. El cliente ya tiene el resto del estado;
esto le dice qué ha cambiado.

### Por qué el canal es público

Estas lecturas se sirven **sin autenticar** por
`GET /api/v2/weather-stations/{id}`. Pedir un token para escuchar por socket lo
que se puede leer por HTTP sin él no protegería nada y complicaría a las ocho
webs que consumen la API. Los canales privados —los que sí hay que
autorizar— se declaran en `routes/channels.php`.

### Por qué hay un evento y no nueve

Antes había nueve (`TemperatureUpdateEvent`, `HumidityUpdateEvent`…), el mismo
fichero con distinto nombre, y **los nueve emitían al mismo canal**: la
separación no permitía ni suscribirse a un sensor suelto.

Además no se emitieron nunca. Colgaban de `$dispatchesEvents['created']` de
cada modelo, y el camino de escritura de la API inserta el lote con `insert()`
del query builder, que no pasa por Eloquent y por tanto **no dispara eventos de
modelo**. En `main` el driver de broadcasting era `log`, así que tampoco allí.

Están en `_to_delete/`.

---

## 2. Encenderlo en un despliegue

Backend y frontend ya están instalados; sólo falta configurar y arrancar.

### 2.1 Backend

`php artisan reverb:install` **no hace falta**: `config/reverb.php` y la
conexión `reverb` de `config/broadcasting.php` ya están en el repositorio, con
sus comentarios. Lo único que hay que hacer es rellenar las credenciales en el
`.env`:

```bash
php -r 'printf("REVERB_APP_ID=%d\nREVERB_APP_KEY=%s\nREVERB_APP_SECRET=%s\n", random_int(100000,999999), bin2hex(random_bytes(16)), bin2hex(random_bytes(16)));'
```

### 2.2 Frontend

`resources/js/echo.js` ya existe y `resources/js/app.js` lo importa. **No se
instancia nada si `VITE_REVERB_APP_KEY` está vacía**, para que el panel no
intente abrir un socket contra un servidor que no existe.

Reverb habla el protocolo de Pusher: por eso el cliente es `pusher-js`. No hay
ninguna cuenta de Pusher detrás ni sale un byte fuera del servidor.

---

## 3. Variables de entorno

Están documentadas en `.env.example` y `.env.example.production`. Las que
importan y por qué se separan:

| Variable | Qué es |
|---|---|
| `BROADCAST_CONNECTION` | `null` (nada) o `reverb`. En la v1 se llamaba `BROADCAST_DRIVER`; aquí no se mantiene ese nombre |
| `REVERB_HOST` / `REVERB_PORT` / `REVERB_SCHEME` | Cómo **llega** el cliente: lo que ve el navegador. En producción, `ws.dominio.tld:443` por `https` |
| `REVERB_SERVER_HOST` / `REVERB_SERVER_PORT` | Dónde **escucha** el demonio en la máquina. Detrás de nginx se queda en `127.0.0.1`: el demonio no se expone |
| `REVERB_ALLOWED_ORIGINS` | Dominios que pueden abrir un socket, separados por comas. `*` sólo en local |
| `VITE_REVERB_*` | Copias de las anteriores. Vite sólo expone al navegador las que empiezan por `VITE_` |

> ⚠️ `REVERB_ALLOWED_ORIGINS=*` en producción deja que cualquier web abra un
> socket contra el servidor. Es la única de la lista que es un problema de
> seguridad si se deja como está.

---

## 4. Escuchar desde una web

```js
window.Echo.channel(`weather-station.${idDeLaEstacion}`)
    .listen('.readings.received', (evento) => {
        // evento.station_id, evento.sensors, evento.at
    });
```

El punto delante de `.readings.received` no es una errata: le dice a Echo que
es un nombre propio y no la clase PHP. El nombre lo fija `broadcastAs()`
justamente para que mover o renombrar la clase no rompa a los clientes.

El id de la estación principal sale de `GET /api/v2/weather-stations`, que
devuelve una colección con la principal cuando no se le pasa `?zone=`.

---

## 5. La cola

`ReadingsReceived` implementa `ShouldBroadcast`, así que la emisión se
encola. Con `QUEUE_CONNECTION=sync` —el valor por defecto— eso significa que
se emite **dentro de la petición** de la estación: si Reverb no responde, la
subida se queda esperando. Por eso la conexión tiene `timeout => 5`.

En producción, con `QUEUE_CONNECTION=database` y un worker corriendo, la subida
responde y el aviso sale por detrás. Es la configuración recomendada.

---
## 6. Puesta en marcha

Todo lo que hay que hacer en la máquina —el demonio (systemd o Supervisor), el
sitio virtual de nginx, el certificado, el cortafuegos y la lista de
comprobación— está en
[`docs/deploys/websockets-reverb.md`](../deploys/websockets-reverb.md), que es
donde vive la documentación de despliegue.

> ⚠️ De ahí, lo único que puede hacer daño si se deja mal:
> **`REVERB_ALLOWED_ORIGINS` no puede quedarse en `*` en producción.** Es lo
> único que impide que cualquier web abra un socket contra el servidor.

---

## 7. Comprobar que funciona

```bash
# 1. ¿Está escuchando?
ss -lntp | grep 8080

# 2. Emitir a mano y ver si el cliente lo recibe
php artisan tinker
>>> event(new \App\Events\WeatherStation\ReadingsReceived(3, ['temperatures' => [['value' => 21.4]]]));
```

Para ver qué se emitiría sin levantar nada, `BROADCAST_CONNECTION=log`: el
evento entero acaba en el log de la aplicación.

Los tests están en `tests/Feature/WeatherStation/ReadingsBroadcastTest.php` y
comprueban lo que fallaba antes sin que nadie se enterara: que **se emite**,
que el lote multisensor emite **uno solo**, que el canal y el nombre del evento
son los pactados, y que una petición rechazada no emite nada.

---

## 8. Qué NO usa WebSockets

- **El panel de Filament.** Sus notificaciones van por polling. Añadirlas por
  socket es posible (`Broadcast::channel('App.Models.User.{id}')` ya está
  declarado) pero hoy no está montado.
- **Los demás módulos IoT** —energía, plantas, contador de pulsaciones,
  vuelos—. No hay pantalla que los mire en vivo. Si algún día la hay, el patrón
  está aquí: un evento por petición, canal por dispositivo, emitido desde el
  controlador después de la transacción.

---

> Creado: 2026-08-19 · Última revisión: 2026-08-30
