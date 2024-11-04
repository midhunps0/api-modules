<?php

namespace Modules\Ynotz\AccessControl;

use Illuminate\Support\ServiceProvider;

class AccessControlProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/Database/migrations/');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'accesscontrol');
    }
}
