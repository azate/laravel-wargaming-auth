<?php

declare(strict_types=1);

namespace Azate\Laravel\WargamingAuth\Providers;

use Illuminate\Support\ServiceProvider;

class WargamingAuthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfig();
    }

    /**
     * Setup the config.
     *
     * @return void
     */
    protected function setupConfig()
    {
        $configPath = realpath(__DIR__ . '/../../config/wargaming_auth.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $configPath => $this->app->configPath('wargaming_auth.php'),
            ]);
        }

        if (!$this->app->configurationIsCached()) {
            $this->mergeConfigFrom($configPath, 'wargaming_auth');
        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(WargamingAuth::class, function () {
            return new WargamingAuth($this->app['request']);
        });
    }
}
