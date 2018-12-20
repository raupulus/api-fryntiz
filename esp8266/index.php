<?php
/**
 *
 * @About      API Interface
 * @version    $Rev:$ 0.0.1
 * @author     Raúl Caro Pastorino (fryntiz@gmail.com)
 * @copyright  Copyright © 2018 Raúl Caro Pastorino
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html
 **/

// Headers para poder acceder desde otros dominios
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: X-Requested-With");
header('Content-Type: text/html; charset=utf-8');
header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');

require '../index.php';

use app\helpers\EchoResponse;
use app\components\DbConnect;
use app\components\DbHandler;

/**
 * Incluyo API para esp8266
 */
require_once 'test.php';

$app->run();
