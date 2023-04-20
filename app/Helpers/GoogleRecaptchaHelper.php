<?php

namespace App\Helpers;

use \ReCaptcha\ReCaptcha;
use ReCaptcha\Response;

/**
 * Class GoogleRecaptchaHelper
 *
 * @package App\Helpers
 */
class GoogleRecaptchaHelper
{

    /**
     * Almacena las acciones que están habilitadas para el captcha de google.
     */
    private const ACTIONS = [
        'contact',
    ];

    /**
     * Almacena los hosts que están habilitados para el captcha de google.
     */
    private const HOSTS = [
        'raupulus.dev',
    ];


    /**
     * Devuelve la clave privada de Google Recaptcha.
     *
     * @return string
     */
    private static function getGoogleRecaptchaSecret(): string
    {
        return config('google.google_recaptcha_secret');
    }

    /**
     * Devuelve la clave pública de Google Recaptcha.
     *
     * @return string
     */
    private static function getGoogleRecaptchaKey(): string
    {
        return config('google.google_recaptcha_key');
    }

    /**
     * Comprueba si el nombre del host es válido, contemplado en la lista de hosts.
     *
     * @param string $hostName Nombre del host.
     *
     * @return bool
     */
    private static function validateHostName(string $hostName): bool
    {
        $filter =  filter_var($hostName, FILTER_VALIDATE_URL);

        if ($filter === false) {
            return false;
        }

        return in_array($hostName, self::HOSTS);
    }


    /**
     * Comprueba si la acción es válida, contemplada en la lista de acciones.
     *
     * @param string $action Nombre de la acción.
     *
     * @return bool
     */
    private static function validateAction(string $action): bool
    {
        return in_array($action, self::ACTIONS);
    }

    public static function checkCaptcha(string $token, string $scope, string $requestIp, float $scoreThreshold = 0.5): Response
    {
        $secret = self::getGoogleRecaptchaSecret();
        $recaptcha = new ReCaptcha($secret);

        return $recaptcha->setExpectedAction($scope)
            ->setScoreThreshold($scoreThreshold)
            ->verify($token, $requestIp);
    }


}
