<?php

namespace Modules\Ynotz\AccessControl\Http\Controllers;

use Modules\Ynotz\EasyApi\Traits\ApiMethodsHelper;

class UsersController
{
    use ApiMethodsHelper;

    public function __construct(){
        $this->connectorService = app()->make(config('easyapi.user_service'));
    }
}
