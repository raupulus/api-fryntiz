<?php
/**
 * Created by PhpStorm.
 * Date: 22/05/2021
 * Time: 18:19
 * @author Raúl Caro Pastorino
 * @copyright Copyright © 2021 Raúl Caro Pastorino
 * @license https://www.gnu.org/licenses/gpl-3.0-standalone.html
*/

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class JsonHelper
 */
class JsonHelper
{
    /**
     * Mensaje confirmando procedimiento correcto.
     *
     * @var array
     */
    private static $success = [
        'status' => 'ok',  // Estado de la petición.
    ];

    /**
     * Mensaje que declara un error en el proceso.
     *
     * @var array
     */
    private static $error = [
        'status' => 'ko',  // Estado de la petición
        'source' => [  // Información sobre el origen.
            'domain' => null,
            'url' => null,
            'full_url' => null,
            'path' => null,
        ],
        'error' => [
            'codeError' => 0,  // Código de error interno a la app.
            'httpCode' => 400,  // Codigo http para el error.
            'message' => 'Error',  // Mensaje del error.
            'exception' => null,  // Pila de errores.
        ],
    ];

    /**
     * Mensaje indicando que hay un problema con los datos recibidos.
     *
     * @var array
     */
    private static $fail = [
        'status' => 'ko',  // Estado de la petición.
        'httpCode' => 422,  // Codigo http para el error.
        'codeError' => 0, // Código de error interno a la app.
        'message' => 'The given data was invalid.',
        'source' => [  // Información sobre el origen.
            'domain' => null,
            'url' => null,
            'full_url' => null,
            'path' => null,
        ],
        'errors' => [],
    ];

    /**
     * Devuelve un array con el formato de respuesta para mezclar con el
     * $success o el $error rellenando la información sobre el sitio.
     *
     * @return array[]
     */
    private static function siteData()
    {
        // TODO → Plantear si interesa solo cuando se está en debug, mejor rendimiento??

        return [
            'source' => [
                'domain' => request()->getHost(),
                'url' => request()->root(),
                'full_url' => request()->fullUrl(),
                'path' => request()->path(),
                'parameters' => request()->all(),
                'headers' => request()->headers->all(),
            ],

            /*
            'link' => [
                'rel' => 'self',
                'href' => request()->fullUrl(),

            ],
            */
        ];
    }


    /**
     * Prepara un mensaje de respuesta cuando se ha llevado a cabo correctamente
     * la petición.
     *
     * @param array       $data Datos de la respuesta.
     *
     * @return array
     */
    private static function prepareSuccess(Array $data)
    {
        return array_merge(
            self::$success,
            //self::siteData(),
            $data,
        );
    }

    /**
     * Prepara un mensaje de error para una acción fallida.
     *
     * @param String|null     $message Mensaje en formato humano.
     * @param Int             $httpCode Código http del error.
     * @param \Exception|null $exception Excepción de seguimiento.
     * @param Int|null        $codeError Id del error dentro de la aplicación.
     *
     * @return array
     */
    private static function prepareError(String $message = 'Error',
                                 Int $httpCode = 400,
                                 Exception $exception = null,
                                 Int $codeError = null)
    {
        return array_merge(
            self::$error,
            self::siteData(),
            [
                'error' => [
                    'codeError' => $codeError,
                    'httpCode' => $httpCode,
                    'message' => $message,
                    'exception' => $exception,
                ],
            ],
        );
    }

    /**
     * Prepara un mensaje de respuesta cuando se reciben parámetros mal.
     *
     * @param String $message Mensaje de error.
     * @param array  $errors Array con los errores de validación.
     * @param Int    $httpCode Código http de la respuesta.
     * @param Int    $codeError Código de error interno para la aplicación.
     *
     * @return array
     */
    private static function prepareFail(String $message = 'The given data was invalid.',
                                       Array $errors = [],
                                       Int $httpCode = 422,
                                       Int $codeError = 0)
    {
        return array_merge(
            self::$fail,
            self::siteData(),
            [
                'httpCode' => $httpCode,
                'codeError' => $codeError,
                'message' => $message,
                'errors' => $errors,
            ],
        );
    }

    /**
     * Respuesta genérica para devolver algo correctamente.
     *
     * @param array $data
     *
     * @return \Illuminate\Http\JsonResponse Devuelve la respuesta final.
     */
    public static function success(Array $data = [])
    {
        return response()->json(self::prepareSuccess($data), 200);
    }

    /**
     * Respuesta indicando que se ha creado un elemento correctamente.
     *
     * @param array $data
     *
     * @return \Illuminate\Http\JsonResponse Devuelve la respuesta final.
     */
    public static function created(Array $data = [])
    {
        return response()->json(self::prepareSuccess($data), 201);
    }

    /**
     * Respuesta indicando que se ha insertado un elemento correctamente.
     *
     * @param array $data
     *
     * @return \Illuminate\Http\JsonResponse Devuelve la respuesta final.
     */
    public static function updated(Array $data = [])
    {
        return response()->json(self::prepareSuccess($data), 202);
    }

    /**
     * Respuesta indicando que se ha eliminado correctamente.
     *
     * @param array $data
     *
     * @return \Illuminate\Http\JsonResponse Devuelve la respuesta final.
     */
    public static function deleted(Array $data = [])
    {
        return response()->json(self::prepareSuccess($data), 200);
    }

    /**
     * Respuesta indicando que la petición ha sido aceptada.
     *
     * @param array $data Datos de la respuesta.
     *
     * @return \Illuminate\Http\JsonResponse Devuelve la respuesta final.
     */
    public static function accepted(Array $data = [])
    {
        return response()->json(self::prepareSuccess($data), 202);
    }

    /**
     * Respuesta indicando que la petición contiene parámetros erróneos.
     *
     * @param String $message Mensaje de error.
     * @param array  $errors Array con los errores de validación.
     * @param Int    $httpCode Código http de la respuesta.
     * @param Int    $codeError Código de error interno para la aplicación.
     *
     * @return \Illuminate\Http\JsonResponse Devuelve la respuesta final.
     */
    public static function failed(String $message = 'The given data was invalid.',
                                  Array $errors = [],
                                  Int $httpCode = 422,
                                  Int $codeError = 0)
    {
        return response()->json(self::prepareFail(
                $message,
                $errors,
                $httpCode,
                $codeError
            ),
            $httpCode);
    }

    /**
     * Respuesta indicando que no se encuentra el elemento
     *
     * @param String $message Mensaje de error.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function notFound(String $message = '404 This resource does not exist')
    {
        $httpCode = 404;
        $exception = new NotFoundHttpException($message);
        $codeError = 0;

        return response()->json(self::prepareError(
                $message,
                $httpCode,
                $exception,
                $codeError
            ),
            $httpCode);
    }

    /**
     * Acceso Prohibido, 401|403.
     *
     * @param String          $message Mensaje de error.
     * @param Int             $httpCode Código http de la respuesta.
     * @param Int             $codeError Código de error interno para la aplicación.
     * @param \Exception|null $exception Excepción.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function forbidden(String $message = '403 Forbidden Access',
                                     Int $httpCode = 403,
                                     Int $codeError = 0,
                                     Exception $exception = null)
    {
        $exception = $exception ?? new AccessDeniedHttpException($message);

        return response()->json(self::prepareError(
                $message,
                $httpCode,
                $exception,
                $codeError
            ),
            $httpCode);
    }
}
