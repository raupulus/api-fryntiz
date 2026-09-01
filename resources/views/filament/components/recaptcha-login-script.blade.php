{{--
    reCAPTCHA v3 para los logins de Filament (Admin y Tenant).

    Sin RECAPTCHA_SITE_KEY en el entorno no se renderiza nada: ni script de
    Google ni input oculto. Con clave, el token se refresca en segundo plano
    (caduca a los 2 minutos) y se escribe en un input oculto ligado por
    wire:model a la propiedad `recaptchaToken` del trait HasRecaptchaLogin, sin
    necesidad de interceptar el submit del formulario.
--}}
@if ($siteKey = config('google.recaptcha.site_key'))
    <input type="hidden" wire:model="recaptchaToken" id="recaptcha-login-token">

    <script src="https://www.google.com/recaptcha/api.js?render={{ $siteKey }}"></script>
    <script>
        (function () {
            const siteKey = @js($siteKey);
            const input = document.getElementById('recaptcha-login-token');

            function refreshRecaptchaToken() {
                grecaptcha.ready(function () {
                    grecaptcha.execute(siteKey, {action: 'login'}).then(function (token) {
                        input.value = token;
                        input.dispatchEvent(new Event('input'));
                    });
                });
            }

            refreshRecaptchaToken();
            setInterval(refreshRecaptchaToken, 100000);
        })();
    </script>
@endif
