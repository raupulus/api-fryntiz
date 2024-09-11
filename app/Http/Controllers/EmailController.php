<?php

namespace App\Http\Controllers;


// TODO: Crear configuración para captcha de google v3 en el archivo .env y en el archivo config/app.php
// permitiendo que se pueda trabajar con un token privado por cada plataforma. Además de la principal para este dominio.
// Esto quizás se podría dinamizar en el futuro dentro de "plataformas", pero por ahora no es necesario.
use App\Helpers\GoogleRecaptchaHelper;
use App\Http\Requests\EmailContactSendRequest;
use App\Models\Email;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class EmailController extends Controller
{
    /**
     * Almacena las aplicaciones que están habilitadas para enviar emails.
     *
     * TODO: Dinamizar desde plataformas, así podré tener un email para cada plataforma.
     */
    private const APPS_ENABLED = [
        'raupulus',
        'desdechipiona',
        'fryntiz',
        'laguialinux',
    ];

    /**
     * Almacena los dominios que están habilitados para enviar emails.
     *
     * TODO: Dinamizar desde plataformas, así podré tener un email para cada plataforma.
     */
    private const DOMAINS_ENABLED = [
        'raupulus.dev',
        'fryntiz.dev',
        'desdechipiona.es',
        'laguialinux.es',
        'laguialinux.com',
    ];


    /**
     * Comprueba si el email es válido.
     *
     * @param string $email Cadena con el email a comprobar.
     *
     * @return bool
     */
    private function checkEmail(string $email): bool
    {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Compueba si la aplicación es válida.
     *
     * @param string $app Nombre de la aplicación a comprobar.
     *
     * @return bool
     */
    private function checkApp(string $app): bool
    {
        return in_array($app, self::APPS_ENABLED);
    }

    /**
     * Comprueba si el dominio es válido.
     *
     * @param string $domain Nombre del dominio a comprobar.
     *
     * @return bool
     */
    private function checkDomain(string $domain): bool
    {
        return in_array($domain, self::DOMAINS_ENABLED);
    }

    /**
     * Extrae los atributos secundarios de una petición para almacenarlos en el campo de "attributes" como json.
     *
     * @param Request $request
     *
     * @return JsonResponse|null
     */
    public function getAttributesJson(Request $request): ?JsonResponse
    {
        // TODO: A medida que se vaya necesitando atributos situacionales, se irán añadiendo aquí.

        return null;
    }

    /**
     * Comprueba si la ip está bloqueada por demasiados intentos o en listas de sospechosos habituales.
     *
     * @param string $ip Dirección ip a comprobar.
     *
     * @return bool
     */
    private function checkIpIsAllowed(string $ip): bool
    {
        $ipSlug = Str::slug($ip, '_');
        $intents = Cache::get('ipCount_' . $ipSlug, 0);

        return config('app.debug') ?? ($intents <= 5);
    }

    /**
     * Comprueba la calidad de las palabras del texto y devuelve si pasa los filtros establecidos.
     *
     * @param string $text Texto a analizar.
     *
     * @return float Puntuación del texto, rango 0-10.
     */
    public function checkWordsQuality(string $text): float
    {
        // TODO: Comprobar si el texto contiene palabras sospechosas, si es así, devolver integer del 1-10.
        // TODO: Crear lista de palabras sospechosas y su puntuación, siendo 1 poco sospechosa.
        // Lo que se devuelve es la puntuación total del texto, si es mayor de 3 se bloqueará el envío.

        return 10;
    }

    /**
     * Recibe los datos desde una petición api Post, almacena y envía el email.
     *
     * @param EmailContactSendRequest $request
     * @return JsonResponse
     */
    public function sendFromForm(EmailContactSendRequest $request)
    {
        $checkIp = $this->checkIpIsAllowed($request->ip());
        $checkApp = $this->checkApp($request->get('app_name'));
        $checkDomain = $this->checkDomain($request->get('app_domain'));

        if (!$checkIp || !$checkApp || !$checkDomain) {
            return \JsonHelper::forbidden('Origen erróneo, petición mal formada o ip bloqueada. Enviada alerta de seguridad al administrador');
        }

        $email = new Email($request->validated());

        $errors = [];


        $checkLastFromEmail = Email::where('email', $email->email)
            ->where('created_at', '>=', Carbon::now()->subMinutes(2))
            ->exists();

        if ($checkLastFromEmail) {
            $errors['email_sender'] = 'Ya se ha recibido una solicitud de email con esa dirección de correo. Si es légitimo el envío prueba dentro de unos minutos.';
        }

        $checkSubjectAndMessage = Email::where('email', $email->email)
            ->where(function ($query) use ($email) {
                return $query->where('subject', 'iLike', $email->subject)
                    ->orWhere('message', 'iLike', $email->message);
            })
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->exists();

        if ($checkSubjectAndMessage) {
            $errors['subject_message'] = 'Ya se ha recibido una solicitud de email con el mismo asunto o mensaje en las últimas 24 horas.';
        }


        // TODO: Cuando se termine, no continuar al primer error para ahorrar peticiones a recaptcha si es un mensaje
        // duplicado. Es decir, si no pasa validaciones de email no seguir comprobando captcha

        if ($checkLastFromEmail && $checkSubjectAndMessage) {
            $captchaToken = $request->get('captcha_token');
            $captchaValid = GoogleRecaptchaHelper::checkCaptcha($captchaToken, 'contact', $request->ip());
        }

        if (!$checkLastFromEmail && !$checkSubjectAndMessage && $captchaValid->isSuccess()) {
            // Verified!
            $email->captcha_score = $captchaValid->getScore();
            $qualityCaptcha = $captchaValid->getScore() <= 0.5 ? 0 : ($captchaValid->getScore() - 0.5) * 8;

            $priority = (int)collect([
                'captcha' => $qualityCaptcha,
                'words' => $this->checkWordsQuality($email->message) / 2,
                'attempts' => $this->checkIpIsAllowed($request->ip()) ? 1 : 0,
            ])->sum();

            $email->send = $priority >= 4; ## Umbral mínimo para enviar 4/10
            $email->priority = $priority;
        } else {
            $email->send = false;
            $email->priority = 0;

            $errors['captcha'] = $captchaValid->getErrorCodes();
        }

        ## En caso de mensaje idéntico o parecido descartar guardar
        if (!$checkLastFromEmail && !$checkSubjectAndMessage) {
            $email->attempts = $email->send ?? true;
            $email->save();
        }

        if ($email->send && $this->sendEmail($email, 'contact')) {
            $email->sent_at = Carbon::now();
            $email->save();
        }

        $success = count($errors) ? [] : ['Email enviado correctamente.'];

        return \JsonHelper::success(
            [
                'data' => $email,
                'messages' => [ // Array con los mensajes de estados
                    'success' => $success,
                    'errors' => $errors, // Array con los mensajes de error
                ],
            ],
        );
    }

    /**
     * Realiza el envío del email, solo si la puntuación es alta.
     *
     * @param Email $email Email a enviar.
     * @param string $template Plantilla a utilizar para el email, la vista dentro del directorio.
     *
     * @return bool
     */
    private function sendEmail(Email $email, string $template = 'generic'): bool
    {



        // TODO: Terminar de implementar el envío de emails.



        return true;
    }

}
