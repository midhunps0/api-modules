<?php
namespace Modules\Ynotz\EasyApi\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;

class RouteHelper
{
    public static function getEasyRoutes(
        string $modelName,
        string $urlFragment = null,
        string $controller = null,
        bool $clientRoute = false,
        string $clientSlug = 'clients'
    )
    {
        $m = explode('\\', $modelName);
        $modelName = array_pop($m);
        unset($m);

        if (!isset($controller)) {
            $controller = 'App\\Http\\Controllers\\' . ucfirst(Str::camel($modelName)) . 'Controller';
        }

        $urlFragment = $urlFragment ? Str::lower($urlFragment) : Str::plural(Str::lower($modelName));
        $urlStart = $clientRoute ? "/$clientSlug/{clientId}/" : '/';

        Route::get($urlStart.$urlFragment.'/select-ids', [$controller, 'selectIds'])->name($urlFragment.'.selectIds');
        Route::get($urlStart.$urlFragment.'/suggest-list', [$controller, 'suggestlist'])->name($urlFragment.'.suggestlist');
        Route::get($urlStart.$urlFragment.'/download', [$controller, 'download'])->name($urlFragment.'.download');
        Route::get($urlStart.$urlFragment, [$controller, 'index'])->name($urlFragment.'.index');
        Route::post($urlStart.$urlFragment, [$controller, 'store'])->name($urlFragment.'.store');
        // Route::get($urlStart.$urlFragment.'/create', [$controller, 'create'])->name($urlFragment.'.create');
        Route::get($urlStart.$urlFragment.'/{id}', [$controller, 'show'])->name($urlFragment.'.show');
        Route::put($urlStart.$urlFragment.'/{id}', [$controller, 'update'])->name($urlFragment.'.update');
        Route::delete($urlStart.$urlFragment.'/{id}/destroy', [$controller, 'destroy'])->name($urlFragment.'.destroy');
        // Route::get($urlStart.$urlFragment.'/{id}/edit', [$controller, 'edit'])->name($urlFragment.'.edit');
    }
}
?>
