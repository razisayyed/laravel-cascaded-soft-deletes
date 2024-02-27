<?php

namespace RaziAlsayyed\LaravelCascadedSoftDeletes\Providers;

use Illuminate\Support\ServiceProvider;

class CascadedSoftDeletesProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'cascaded-soft-deletes');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('cascaded-soft-deletes.php'),
            ], 'config');
        }
    }
}
