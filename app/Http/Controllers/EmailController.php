<?php

namespace App\Http\Controllers;


// TODO: Crear modelo
// TODO: Crear validación de request
// TODO: Implementar validación del token captcha de google
// TODO: Crear configuración para captcha de google v3 en el archivo .env y en el archivo config/app.php
// permitiendo que se pueda trabajar con un token privado por cada plataforma. Además de la principal para este dominio.
// Esto quizás se podría dinamizar en el futuro dentro de "plataformas", pero por ahora no es necesario.
use App\Models\Email;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    /**
     * Almacena las aplicaciones que están habilitadas para enviar emails.
     */
    private const APPS_ENABLED = [
        'raupulus',
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

    private function checkApp(string $app): bool
    {
        return in_array($app, self::APPS_ENABLED);
    }


    /**
     * Realiza el envío del email, solo si la puntuación es alta.
     * En caso de no ser una puntuación alta, se almacena en la base de datos marcado como no enviado.
     * En caso de ser una puntuación alta, se envía el email y se almacena en la base de datos marcado como enviado.
     * En caso de que el email no se envíe, se almacena en la base de datos marcado como error de envío en un timestamp.
     *
     * @param array $data
     *
     * @return bool
     */
    private function sendEmail(array $data): bool
    {

    }

    /**
     * TODO: Comprobar intentos de envíos desde esta ip, si se supera el límite de intentos
     * se bloquea la ip un tiempo. Quizás tenga que crear lista de bans temporales.
     *
     * @param string $ip
     *
     * @return bool
     */
    private function checkIpIsAllowed(string $ip): bool
    {

    }

    /**** AJAX ****/

    /**
     *
     * TODO: Comprobar las validaciones de los datos.
     * TODO: Responder con un json con el resultado de la operación.
     * TODO: Crear validación de request solo para que los datos lleguen, no para descartar la petición
     * ya que realmente no se está enviando ningún dato todavía.
     * TODO: Almacenar los datos en la base de datos, marcando que no se envían aún.
     * @param array $data
     *
     * @return bool
     */
    public function checkValidations(array $data): bool
    {
        return $this->checkEmail($data['email'])
            && $this->checkApp($data['app']);
    }



    public function send(Request $request)
    {
        $email = new Email([
            'user_id' => null,
            'email' => null,
            'subject' => null,
            'message' => null,
            'privacity' => true,
            'contactme' => true,
            'captcha_score' => null,
            'server_ip' => null,
            'client_ip' => null,
            'app_name' => null,
            'app_domain' => null,
            'attributes' => null,
            'priority' => null,
            //'send' => null, //¿Debería enviarse? según la puntuación del captcha.
            //'attemps' => null, // Intentos de envío.
            //'sent_at' => null,
            //'error_code' => null,
            //'error_at' => null,
            //'error_message' => null,
        ]);

        $token = $request->token;
        $captchaValid = $this->checkCaptcha($token);

        if ($captchaValid->isSuccess()) {
            // Verified!
        } else {
            $errors = $captchaValid->getErrorCodes();
        }


        return \JsonHelper::success(
            [
                'message' => 'Email enviado correctamente.',
                'data' => $email,
            ],
        );
    }



}
