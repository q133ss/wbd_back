<?php

namespace App\Providers;

use App\Auth\ConcreteTelegramSessionGuard;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('ru');

        Auth::extend('telegram-session', function ($app, $name, array $config) {
            $provider = Auth::createUserProvider($config['provider']);
            return new ConcreteTelegramSessionGuard($provider, $app['request']);
        });
    }
}
