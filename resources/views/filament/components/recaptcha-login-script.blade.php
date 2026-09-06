{{--
    reCAPTCHA v3 para los logins de Filament (Admin y Tenant).

    Sin RECAPTCHA_SITE_KEY en el entorno no se renderiza nada: ni script de
    Google ni input oculto. Con clave, el token se refresca en segundo plano
    (caduca a los 2 minutos) y se inyecta en las peticiones de Livewire mediante
    el hook `commit` y la sincronización con el componente.
--}}
@if ($siteKey = config('google.recaptcha.site_key'))
    <input type="hidden" wire:model="recaptchaToken" id="recaptcha-login-token">

    <script src="https://www.google.com/recaptcha/api.js?render={{ $siteKey }}"></script>
    <script>
        (function () {
            const siteKey = @js($siteKey);
            let currentToken = null;

            function syncToken(token) {
                if (!token) return;
                currentToken = token;

                const input = document.getElementById('recaptcha-login-token');
                if (input) {
                    input.value = token;
                    input.setAttribute('value', token);
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }

                const form = document.getElementById('form') || document.querySelector('form');
                if (form) {
                    let formInput = form.querySelector('input[name="recaptchaToken"]');
                    if (!formInput) {
                        formInput = document.createElement('input');
                        formInput.type = 'hidden';
                        formInput.name = 'recaptchaToken';
                        form.appendChild(formInput);
                    }
                    formInput.value = token;
                }

                if (window.Livewire) {
                    const el = document.getElementById('recaptcha-login-token');
                    const rootEl = el ? el.closest('[wire\\:id]') : document.querySelector('[wire\\:id]');
                    if (rootEl) {
                        const component = window.Livewire.find(rootEl.getAttribute('wire:id'));
                        if (component) {
                            if (typeof component.queueUpdate === 'function') {
                                component.queueUpdate('recaptchaToken', token);
                            }
                            if (component.$wire && typeof component.$wire.set === 'function') {
                                component.$wire.set('recaptchaToken', token, false);
                            }
                        }
                    }
                }
            }

            function refreshRecaptchaToken(callback) {
                if (typeof grecaptcha === 'undefined' || typeof grecaptcha.ready !== 'function') {
                    return;
                }
                grecaptcha.ready(function () {
                    grecaptcha.execute(siteKey, { action: 'login' })
                        .then(function (token) {
                            syncToken(token);
                            if (typeof callback === 'function') {
                                callback(token);
                            }
                        })
                        .catch(function (e) {
                            console.error('reCAPTCHA error:', e);
                        });
                });
            }

            function bindLivewireHook() {
                if (window.Livewire && typeof window.Livewire.hook === 'function') {
                    window.Livewire.hook('commit', function ({ component, commit }) {
                        if (currentToken && commit && commit.updates) {
                            commit.updates['recaptchaToken'] = currentToken;
                        }
                    });
                    return true;
                }
                return false;
            }

            if (!bindLivewireHook()) {
                document.addEventListener('livewire:init', bindLivewireHook, { once: true });
                document.addEventListener('livewire:initialized', bindLivewireHook, { once: true });
            }

            document.addEventListener('livewire:initialized', function () {
                if (currentToken) {
                    syncToken(currentToken);
                }
            });

            function initToken() {
                if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.ready === 'function') {
                    refreshRecaptchaToken();
                } else {
                    setTimeout(initToken, 100);
                }
            }
            initToken();

            setInterval(function () {
                refreshRecaptchaToken();
            }, 90000);
        })();
    </script>
@endif
