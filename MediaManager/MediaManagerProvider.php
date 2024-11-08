<?php

namespace Modules\Ynotz\MediaManager;

use Illuminate\Support\ServiceProvider;

class MediaManagerProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // $this->mergeConfigFrom(__DIR__ . '/../config/mediamanager.php', 'mediamanager');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes/api.php', 'mediamanager');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->publishes([
            __DIR__.'/config/mediaManager.php' => config_path('mediaManager.php'),

        ], ['mediamanager-config', 'mediamanager', 'base-modules']);
    }
}
