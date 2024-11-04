<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Modules\Ynotz\AccessControl\Http\Controllers\PermissionsController;
use Modules\Ynotz\AccessControl\Http\Controllers\RolesController;
use Modules\Ynotz\AccessControl\Http\Controllers\UsersController;
use Modules\Ynotz\AccessControl\Models\Permission;
use Modules\Ynotz\AccessControl\Models\Role;
use Modules\Ynotz\EasyAdmin\Services\RouteHelper;

Route::group(['middleware' => ['web', 'auth'], 'prefix' => 'manage'], function () {
    // Route::get('/roles/select-ids', [RolesController::class, 'selectIds'])->name('roles.selectIds');
    // Route::get('/roles/suggest-list', [RolesController::class, 'suggestlist'])->name('roles.suggestlist');
    // Route::get('/roles/download', [RolesController::class, 'download'])->name('roles.download');
    // Route::resource('roles', RolesController::class);
    // Route::get('/permissions/select-ids', [PermissionsController::class, 'selectIds'])->name('permissions.selectIds');
    // Route::resource('permissions', PermissionsController::class);
    RouteHelper::getEasyRoutes(modelName: Role::class, controller: RolesController::class);
    RouteHelper::getEasyRoutes(modelName: Permission::class, controller: PermissionsController::class);
    RouteHelper::getEasyRoutes(modelName: User::class, controller: UsersController::class);
    Route::get('/roles-permissions', [RolesController::class, 'rolesPermissions'])->name('roles.permissions');
    Route::post('/roles/permission-update', [RolesController::class, 'permissionUpdate'])->name('roles.update_permissions');

    // Route::get('/users/select-ids', [UsersController::class, 'selectIds'])->name('users.selectIds');
    // Route::get('users/download', [UsersController::class, 'download'])->name('users.download');
    // Route::resource('users', UsersController::class);
});

?>
