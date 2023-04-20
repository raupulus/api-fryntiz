<?php

namespace App\Http\Controllers;


// TODO: Crear modelo
// TODO: Crear validación de request
// TODO: Implementar validación del token captcha de google
// TODO: Crear configuración para captcha de google v3 en el archivo .env y en el archivo config/app.php
// permitiendo que se pueda trabajar con un token privado por cada plataforma. Además de la principal para este dominio.
// Esto quizás se podría dinamizar en el futuro dentro de "plataformas", pero por ahora no es necesario.
use App\Helpers\GoogleRecaptchaHelper;
use App\Models\Email;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ReCaptcha\Response;

class EmailController extends Controller
{
    /**
     * Almacena las aplicaciones que están habilitadas para enviar emails.
     *
     * TODO: Dinamizar desde plataformas, así podré tener un email para cada plataforma.
     */
    private const APPS_ENABLED = [
        'raupulus',
    ];

    /**
     * Almacena los dominios que están habilitados para enviar emails.
     *
     * TODO: Dinamizar desde plataformas, así podré tener un email para cada plataforma.
     */
    private const DOMAINS_ENABLED = [
        'raupulus.dev',
        'fryntiz.dev',
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
        // TODO: Comprobar intentos de envíos desde esta ip, si se supera el límite de intentos se bloquea la ip un tiempo. Quizás tenga que crear lista de bans temporales.

        return true;
    }

    /**
     * Comprueba la calidad de las palabras del texto y devuelve si pasa los filtros establecidos.
     *
     * @param string $text Texto a analizar.
     *
     * @return bool
     */
    public function checkWordsQuality(string $text): bool
    {
        // TODO: Comprobar si el texto contiene palabras sospechosas, si es así, devolver integer del 1-10.
        // TODO: Crear lista de palabras sospechosas y su puntuación, siendo 1 poco sospechosa.
        // Lo que se devuelve es la puntuación total del texto, si es mayor de 3 se bloqueará el envío.

        return true;
    }

    /**
     * Comprueba si el idioma es válido, además lo busca de la request en caso de no recibirlo.
     *
     * @param Request $request Petición a comprobar.
     * @param string|null $language Idioma a comprobar.
     *
     * @return int|null
     */
    public function searchLanguage(Request $request, string|null $language): ?int
    {
        // Buscar en este orden de formatos: 'es_ES', 'es-ES', 'es'

        // TODO: Comprobar si el idioma es válido, si no lo es, buscar el idioma por defecto.
        // TODO: Buscar el idioma por defecto en la base de datos.


        return null;
    }

    /**
     * Devuelve un array con los idiomas que envía el cliente en la petición.
     * Esto lo hace a partir de la cabecera HTTP_ACCEPT_LANGUAGE que envía el cliente (Navegador).
     *
     * @param Request $request Petición a comprobar.
     *
     * @return array
     */
    public function getClientLanguagesFromRequest(Request $request): array
    {
        $languages = $request->server('HTTP_ACCEPT_LANGUAGE'); // 'en-us,en;q=0.8,es-es;q=0.5,zh-cn;q=0.3,fr;q=0.1';

        if (empty($languages)) {
            return [];
        }

        $array = explode(',', $languages); // ['en-us', 'en;q=0.8', 'es-es;q=0.5', 'zh-cn;q=0.3', 'fr;q=0.1']

        /**
         * Busca en la cadena recibida el idioma y su puntuación.
         *
         * @param string $value Cadena con el idioma y su puntuación.
         *
         * @return array|null
         */
        function mapExtractLanguages(string $value): ?array
        {
            $explode = explode(';', $value);

            if (!$explode || !is_array($explode) || !count($explode)) {
                return null;
            }

            ## Cuando no hay puntuación, se asigna 1.0 https://developer.mozilla.org/en-US/docs/Glossary/Quality_values
            $score = count($explode) === 2 ? (float)trim($explode[1]) : 1.0;

            $isoLocale = mb_strtolower(trim($explode[0]));

            if ($isoLocale === '*') {
                return null;
            }

            $iso2 = trim(substr($isoLocale, 0, 2));

            if (strlen($isoLocale) === 5) {
                $locale = $iso2 . '_' . strtoupper(substr($isoLocale, 3, 5));
            } else {
                $locale = $iso2 . '_' . strtoupper($iso2);
                $isoLocale = $iso2 . '-' . $iso2;
            }

            return [
                'locale' => $locale, // 'es_ES'
                'iso_locale' => $isoLocale, // 'es-es'
                'iso2' => $iso2, // 'es'
                'score' => $score, // '0.8'
            ];
        }

        try {
            $arrayPrepared = array_map(fn ($value): array => mapExtractLanguages($value) , $array);

            $arrayFilter = collect(array_filter($arrayPrepared));

            return $arrayFilter->sortByDesc('score')->unique('locale')->toArray();

        } catch (\Exception $e) {
            return [];
        }
    }

    public function sendFromForm(Request $request)
    {
        $email = new Email([
            'user_id' => auth()->id(),
            'language_id' => $this->searchLanguage($request, $request->get('language')),
            'email' => $request->get('email'),
            'subject' => $request->get('subject'),
            'message' => $request->get('message'),
            'privacity' => $request->get('privacity'),
            'contactme' => $request->get('contactme'),
            'server_ip' => null,
            'client_ip' => $request->ip(),

            'client_user_agent' => $request->userAgent(),
            'client_referer' => $request->server('HTTP_REFERER'), // Página de donde viene el usuario. Mirar si interesa comprobar que si no es de mis dominios... bajarle la puntuación
            'client_accept_language' => $this->getClientLanguagesFromRequest($request),

            'app_name' => $request->get('app_name'),
            'app_domain' => $request->get('app_domain'),
            'attributes' => $this->getAttributesJson($request),


            //'priority' => null, // Prioridad del email según captcha y quizás analizando palabras en el contenido.
            //'captcha_score' => null,
            //'send' => null, //¿Debería enviarse? según la puntuación del captcha.
            //'attemps' => null, // Intentos de envío.
            //'sent_at' => null,
            //'error_code' => null,
            //'error_at' => null,
            //'error_message' => null,
        ]);


        $token = $request->get('token');
        $captchaValid = GoogleRecaptchaHelper::checkCaptcha($token, 'contact', $request->ip());

        $errors = $this->checkValidations($email, $captchaValid);

        if ($captchaValid->isSuccess()) {
            // Verified!
            $email->captcha_score = $captchaValid->getScore();
            $email->send = $captchaValid->getScore() >= 0.5;

            //$email->priority = $captchaValid->getScore() >= 0.5 ? 1 : 0; // Calcular la prioridad según el captcha y quizás analizando palabras en el contenido.
        } else {
            $errors = $captchaValid->getErrorCodes();
        }

        //$email->save();

        if ($email->send) {
            $email->attemps = 1;
            //$email->save();

            //$email->send();
            //$email->sent_at = Carbon::now();
        }

        return \JsonHelper::success(
            [
                'message' => 'Email enviado correctamente.',
                'data' => $email,
                'errors' => $errors,
            ],
        );
    }

    /**
     * Comprueba todas las validaciones a la vez y devuelve un array con los errores.
     * Si no hay errores, devuelve array vacío.
     *
     * @param Email $email Email a validar.
     * @param  $captchaValid // TODO: ¿Qué tipo de dato es? Mirar la librería de google.
     *
     * @return array
     */
    public function checkValidations(Email $email, Response $captchaValid): array
    {
        // TODO: Validar todos los datos y devolver una mochila de errores.

        return [];
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
