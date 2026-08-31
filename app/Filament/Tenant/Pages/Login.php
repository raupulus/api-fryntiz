<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Pages;

use App\Filament\Concerns\HasRecaptchaLogin;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;

class Login extends BaseLogin
{
    use HasRecaptchaLogin;

    public function authenticate(): ?LoginResponse
    {
        $this->verifyRecaptcha();

        return parent::authenticate();
    }
}
