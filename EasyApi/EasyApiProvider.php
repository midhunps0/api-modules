<?php

namespace Modules\Ynotz\EasyApi;

use Illuminate\Support\ServiceProvider;
use Modules\Ynotz\EasyApi\Commands\MakeEasyapiCommand;

class EasyApiProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // $this->mergeConfigFrom(__DIR__ . '/../config/easyapi.php', 'easyapi');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeEasyapiCommand::class
            ]);
        }
        $this->publishes([
            __DIR__.'/config/easyapi.php' => config_path('easyapi.php'),

        ], ['easyapi-config', 'easyapi', 'base-modules']);

    }
}
