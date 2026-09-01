# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {TU_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Los tokens se obtienen con <code>POST /api/v2/auth/tokens</code> (sesión) o desde el panel de usuario, en <b>Tokens de API</b>, para los dispositivos. Un token de dispositivo lleva sólo las abilities del módulo que necesite: no existe comodín.
