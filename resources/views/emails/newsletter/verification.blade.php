<h2>Verifica tu suscripción</h2>

<p>
    Para completar tu suscripción a la newsletter de {{ config('app.name') }}, abre este
    enlace y pulsa el botón de confirmar:
</p>

{{--
    El enlace lleva a una página que NO confirma nada por sí sola: hay que pulsar
    un botón. Antes apuntaba a `api.v2.newsletter.verify`, una ruta GET que
    confirmaba al abrirla — y los antivirus de correo abren todos los enlaces de
    un mensaje al escanearlo. Además pasaba `$newsletter->token`, propiedad que
    no existe (es `verification_token`), así que la URL salía sin token.
--}}
<p>
    <a href="{{ $newsletter->getVerificationUrl() }}">Confirmar mi suscripción</a>
</p>

<p>Si no puedes pulsar el enlace, copia esta dirección en tu navegador:</p>
<p>{{ $newsletter->getVerificationUrl() }}</p>

<hr>

<p style="font-size: 12px; color: #777;">
    Si no has sido tú, ignora este correo: sin confirmar, la suscripción no llega a
    activarse. También puedes
    <a href="{{ $newsletter->getUnsubscribeUrl() }}">darte de baja</a>.
</p>
