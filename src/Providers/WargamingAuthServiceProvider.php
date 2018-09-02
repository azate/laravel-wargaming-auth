<?php

declare(strict_types=1);

namespace Azate\Laravel\WargamingAuth\Providers;

use Azate\Laravel\WargamingAuth\WargamingAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

/**
 * Class WargamingAuthServiceProvider.
 *
 * @package Azate\Laravel\WargamingAuth\Providers
 */
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
        $this->setupRoutePattern();
    }

    /**
     * Setup the config.
     *
     * @return void
     */
    protected function setupConfig()
    {
        $configPath = realpath(__DIR__ . '/../../config/wargamingAuth.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $configPath => $this->app->configPath('wargamingAuth.php'),
            ]);
        }

        if (!$this->app->configurationIsCached()) {
            $this->mergeConfigFrom($configPath, 'wargamingAuth');
        }
    }

    /**
     * Setup the route pattern.
     *
     * @return void
     */
    protected function setupRoutePattern()
    {
        $regions = $this->app['config']->get('wargamingAuth.regions');

        $pattern = (new Collection($regions))
            ->filter()
            ->keys()
            ->implode('|');

        $this->app['router']->pattern('wargamingAuthRegion', $pattern);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(WargamingAuth::class, function () {
            return new WargamingAuth(
                $this->app['config'],
                $this->app['request'],
                $this->app['url']
            );
        });
    }
}
