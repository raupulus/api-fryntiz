<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '/webhook'], function () {
    Route::any('/api-deploy', 'App\Http\Controllers\WebHooks\GitlabWebhookController@apiDeploy')
        ->name('webhook-api-deploy');
    Route::any('/api-notification', 'App\Http\Controllers\WebHooks\GitlabWebhook@apiNotification')
        ->name('webhook-api-notification');
});
