<?php

namespace LHDev\PinmetoLaravel;

use Illuminate\Support\ServiceProvider;

class PinMeToServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/pinmeto.php' => config_path('pinmeto.php')
        ]);
    }

    public function register(): void
    {
        $this->app->bind(Pinmeto::class, function() {
            $config_data = [
                'app_id' => config('pinmeto.app_id'),
                'app_secret' => config('pinmeto.app_secret'),
                'account_id' => config('pinmeto.account_id'),
                'mode' => config('pinmeto.mode'),
            ];

            return new Pinmeto($config_data);
        });
    }
}
