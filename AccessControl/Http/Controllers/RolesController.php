<?php

namespace Modules\Ynotz\AccessControl\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Ynotz\EasyApi\Traits\ApiMethodsHelper;

class RolesController
{
    use ApiMethodsHelper;

    public function __construct(){
        $this->connectorService = app()->make(config('easyapi.role_service'));
    }

    public function rolesPermissions()
    {
        try {
            response()->json([
                'success' => true,
                'data' => $this->connectorService->rolesPermissionsData()
            ]);
        } catch (\Throwable $e) {
            $debug = env('APP_DEBUG');
            if ($debug) { info($e); }
            return response()->json([
                'success' => false,
                'error' => $debug ? $e->__toString() : $e->getMessage()
            ]);
        }
    }

    public function permissionUpdate(Request $request)
    {
        try {
            response()->json([
                'success' => true,
                'data' => $this->connectorService->permissionUpdate($request->all())
            ]);
        } catch (\Throwable $e) {
            $debug = env('APP_DEBUG');
            if ($debug) { info($e); }
            return response()->json([
                'success' => false,
                'error' => $debug ? $e->__toString() : $e->getMessage()
            ]);
        }
    }
}
