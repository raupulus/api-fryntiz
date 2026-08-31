/**
 * Cliente de WebSocket (Laravel Echo sobre Reverb).
 *
 * Reverb habla el protocolo de Pusher, por eso el cliente es `pusher-js`: no
 * hay ninguna cuenta de Pusher detrás ni sale un byte fuera del servidor.
 *
 * Las variables llegan del `.env` por Vite. Si no está configurado —que es el
 * caso por defecto, con `BROADCAST_CONNECTION=null`— no se instancia nada: así
 * el panel no intenta abrir un socket contra un servidor que no existe y
 * llenar la consola de errores de reconexión.
 */
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const appKey = import.meta.env.VITE_REVERB_APP_KEY;

if (appKey) {
    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: appKey,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}

export default window.Echo ?? null;
