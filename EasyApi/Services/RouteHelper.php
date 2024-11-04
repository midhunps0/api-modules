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
    )
    {
        $m = explode('\\', $modelName);
        $modelName = array_pop($m);
        unset($m);

        if (!isset($controller)) {
            $controller = 'App\\Http\\Controllers\\' . ucfirst(Str::camel($modelName)) . 'Controller';
        }

        $urlFragment = $urlFragment ? Str::lower($urlFragment) : Str::plural(Str::lower($modelName));

        Route::get('/'.$urlFragment.'/select-ids', [$controller, 'selectIds'])->name($urlFragment.'.selectIds');
        Route::get('/'.$urlFragment.'/suggest-list', [$controller, 'suggestlist'])->name($urlFragment.'.suggestlist');
        Route::get('/'.$urlFragment.'/download', [$controller, 'download'])->name($urlFragment.'.download');
        Route::get('/'.$urlFragment, [$controller, 'index'])->name($urlFragment.'.index');
        Route::post('/'.$urlFragment, [$controller, 'store'])->name($urlFragment.'.store');
        // Route::get('/'.$urlFragment.'/create', [$controller, 'create'])->name($urlFragment.'.create');
        Route::get('/'.$urlFragment.'/{id}', [$controller, 'show'])->name($urlFragment.'.show');
        Route::put('/'.$urlFragment.'/{id}', [$controller, 'update'])->name($urlFragment.'.update');
        Route::delete('/'.$urlFragment.'/{id}/destroy', [$controller, 'destroy'])->name($urlFragment.'.destroy');
        // Route::get('/'.$urlFragment.'/{id}/edit', [$controller, 'edit'])->name($urlFragment.'.edit');
    }
}
?>
