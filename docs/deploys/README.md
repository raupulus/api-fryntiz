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
| [`nginx.conf`](nginx.conf) | Sitio virtual de la aplicación (nginx) |
| [`nginx-websocket.conf`](nginx-websocket.conf) | Sitio virtual del WebSocket (`ws.`), con el `upgrade` y los tiempos de espera largos |
| [`apache.conf`](apache.conf) | Sitio virtual de la aplicación (Apache), alternativa a nginx |

## Lo que más se olvida

| | |
|---|---|
| `REVERB_ALLOWED_ORIGINS` | **Nunca `*` en producción.** Es lo único que impide que cualquier web abra un socket contra el servidor |
| Reiniciar los procesos de larga vida | `api-fryntiz-reverb` y los workers de cola mantienen el código en memoria: un `git pull` no les llega |
| `pnpm build` después de tocar las `VITE_*` | Vite las lee **al compilar**, no en tiempo de ejecución |
| El 8080 no se abre en el cortafuegos | El demonio escucha en `127.0.0.1` y delante va nginx |

---

> Creado: 2026-08-30 · Última revisión: 2026-08-30
