<?php

namespace App\Providers;

use App\Models\Hardware\HardwareDevice;
use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;
use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\SmartPlant\SmartPlantRegister;
use App\Models\User;
use App\Policies\HardwarePolicy;
use App\Policies\KeyCounterKeyboardPolicy;
use App\Policies\KeyCounterMousePolicy;
use App\Policies\SmartPlantPolicy;
use App\Policies\SmartPlantRegisterPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

/**
 * Class AuthServiceProvider
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        User::class => UserPolicy::class,
        HardwareDevice::class => HardwarePolicy::class,
        SmartPlantRegister::class => SmartPlantRegisterPolicy::class,
        SmartPlantPlant::class => SmartPlantPolicy::class,
        Keyboard::class => KeyCounterKeyboardPolicy::class,
        Mouse::class => KeyCounterMousePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('viewWebSocketsDashboard', function ($user = null) {
            return $user && in_array($user->role_id, [
                1,
                2,
            ]);
        });
    }
}
