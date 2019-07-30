<?php

use Illuminate\Support\Facades\Route;

Route::group([], function () {
    Route::any('/api-deploy', 'Webhook\GitlabWebhookController@apiDeploy')
        ->name('webhook-api-deploy');
    Route::any('/api-notification', 'Webhook\GitlabWebhook@apiNotification')
        ->name('webhook-api-notification');
});
