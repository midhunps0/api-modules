<?php

use Modules\Ynotz\EasyAdmin\Http\Controllers\DashboardController;
use Modules\Ynotz\EasyAdmin\Services\DashboardService;
use Modules\Ynotz\EasyAdmin\Services\SidebarService;

return [
    'user_service' => Modules\Ynotz\AccessControl\Services\UserService::class,
    'permission_service' => Modules\Ynotz\AccessControl\Services\PermissionService::class,
    'role_service' => Modules\Ynotz\AccessControl\Services\RoleService::class,
    'enforce_validation' => true,
    'paginate_by_default' => true
];
