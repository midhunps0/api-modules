<?php
namespace Modules\Ynotz\AccessControl\Services;

use Modules\Ynotz\AccessControl\Models\Permission;
use Modules\Ynotz\EasyApi\Contracts\ApiCrudHelperContract;
use Modules\Ynotz\EasyApi\Traits\DefaultApiCrudHelper;

class PermissionService implements ApiCrudHelperContract {
    use DefaultApiCrudHelper;
    private $indexTable;

    public function __construct()
    {
        $this->modelClass = Permission::class;
    }

    public function getDownloadCols(): array
    {
        return [
            'id',
            'name'
        ];
    }

    public function getStoreValidationRules(): array
    {
        return [
            'name' => ['required', 'string']
        ];
    }

    public function getUpdateValidationRules($id = null): array
    {
        return [
            'name' => ['required', 'string']
        ];
    }
}

?>
