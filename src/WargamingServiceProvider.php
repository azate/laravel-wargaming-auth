<?php

namespace Azate\Laravel\WargamingAuth;

use Illuminate\Support\ServiceProvider;

class WargamingServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $path = __DIR__ . '/../config/wargaming_auth.php';

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $path => config_path('wargaming_auth.php'),
            ]);
        }

        $this->mergeConfigFrom($path, 'wargaming_auth');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('wargaming.auth', function () {
            return new WargamingAuth($this->app['request']);
        });
    }
}
