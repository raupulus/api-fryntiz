<?php
use app\helpers\EchoResponse;

$app->get('/test', function () {
    $message = 'holaillo';

    $response["error"] = false;
    $response["message"] = 'Correcto';
    $response["text"] = $message;

    EchoResponse::response($response, 200);
});
?>
