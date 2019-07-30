<?php

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

Route::group([], function () {
    Route::post('/api-deploy', '\Webhook\GitlabWebhook@apiDeploy');
    Route::post('/api-notification', '\Webhook\GitlabWebhook@apiNotification');
});
