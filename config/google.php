<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Recaptcha
    |--------------------------------------------------------------------------
    */
    'google_recaptcha_key' => env('GOOGLE_CAPTCHA_KEY'),
    'google_recaptcha_secret' => env('GOOGLE_CAPTCHA_SECRET'),
    ## Google Services
    'google_api_key' => config('app.debug') ? env('GOOGLE_DEV_API_KEY', null) : env('GOOGLE_API_KEY', null),
    'google_dev_api_key' => env('GOOGLE_DEV_API_KEY', null),
];
