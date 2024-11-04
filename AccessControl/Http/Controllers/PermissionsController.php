<?php

namespace Modules\Ynotz\AccessControl\Http\Controllers;

use Modules\Ynotz\EasyApi\Traits\ApiMethodsHelper;

class PermissionsController
{
    use ApiMethodsHelper;

    public function __construct(){
        $this->connectorService = app()->make(config('easyapi.permission_service'));
    }
}
