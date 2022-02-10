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
        'source' => [  // Información sobre el origen.
            'domain' => null,
            'url' => null,
            'full_url' => null,
            'path' => null,
        ],
        'data' => null,
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
            'id' => null,  // Código de error interno a la app.
            'code' => null,  // Codigo http para el error.
            'message' => null,  // Mensaje del error.
            'trace' => null,  // Pila de errores.
        ],
    ];

    /**
     * Mensaje indicando que hay un problema con los datos recibidos.
     *
     * @var array
     */
    private static $fail = [
        'status' => 'ko',  // Estado de la petición.
        'source' => [  // Información sobre el origen.
            'domain' => null,
            'url' => null,
            'full_url' => null,
            'path' => null,
        ],
        'data' => null,
    ];

    /**
     * Devuelve un array con el formato de respuesta para mezclar con el
     * $success o el $error rellenando la información sobre el sitio.
     *
     * @return array[]
     */
    private static function siteData()
    {
        return [
            'source' => [
                'domain' => request()->getHost(),
                'url' => request()->root(),
                'full_url' => request()->fullUrl(),
                'path' => request()->path(),
                'parameters' => request()->all(),
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
    public static function prepareSuccess(Array $data)
    {
        return array_merge(
            self::$success,
            self::siteData(),
            [
                'data' => $data,
            ],
        );
    }

    /**
     * Prepara un mensaje de error para una acción fallida.
     *
     * @param String|null     $msg Mensaje en formato humano.
     * @param Int             $status Código http del error.
     * @param \Exception|null $trace Excepción de seguimiento.
     * @param Int|null        $id Id del error dentro de la aplicación.
     *
     * @return array
     */
    public static function prepareError(String $msg = 'Error',
                                 Int $status = 400,
                                 Exception $trace = null,
                                 Int $id = null)
    {
        return array_merge(
            self::$error,
            self::siteData(),
            [
                'error' => [
                    'id' => $id,
                    'code' => $status,
                    'trace' => $trace,
                    'message' => $msg,
                ],
            ],
        );
    }

    /**
     * Prepara un mensaje de respuesta cuando se reciben parámetros mal.
     *
     * @param array $data Datos de la respuesta con los atributos que hayan
     *                    fallado.
     *

     * @return array
     */
    public static function prepareFail(Array $data, $message = 'Fail')
    {
        return array_merge(
            self::$fail,
            self::siteData(),
            [
                'data' => $data,
                'message' => $message,
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
     * @param array $data
     *
     * @return \Illuminate\Http\JsonResponse Devuelve la respuesta final.
     */
    public static function failed(Array $data = [])
    {
        return response()->json(self::prepareFail($data), 200);
    }

    /**
     * Respuesta indicando que no se encuentra el elemento
     */
    public static function notFound(String $msg = '404 This resource does not exist')
    {
        $code = 404;
        $exception = new NotFoundHttpException($msg);

        return response()->json(self::prepareError($msg, $code, $exception), $code);
    }

    /**
     * Acceso Prohibido, 403.
     */
    public static function forbidden($msg = '403 Forbidden Access')
    {
        $code = 403;
        $exception = new AccessDeniedHttpException($msg);

        return response()->json(self::prepareError($msg, $code, $exception), $code);
    }
}
