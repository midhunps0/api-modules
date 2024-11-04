<?php
use Illuminate\Support\Facades\Route;
use Modules\Ynotz\EasyAdmin\Http\Controllers\DashboardController;
use Modules\Ynotz\EasyAdmin\Http\Controllers\MasterController;

Route::group(['middleware' => ['web', 'auth', 'verified'], 'prefix' => 'manage'], function () {
    Route::get('dashboard', [config('easyadmin.dashboard_controller'), config('easyadmin.dashboard_method')])->name('dashboard');
});
?>

