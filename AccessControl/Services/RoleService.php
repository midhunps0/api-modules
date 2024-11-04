<?php
namespace Modules\Ynotz\AccessControl\Services;


use Modules\Ynotz\AccessControl\Models\Role;
use Modules\Ynotz\AccessControl\Models\Permission;
use Modules\Ynotz\EasyApi\Contracts\ApiCrudHelperContract;
use Modules\Ynotz\EasyApi\Traits\DefaultApiCrudHelper;

class RoleService implements ApiCrudHelperContract {
    use DefaultApiCrudHelper;
    private $indexTable;

    public function __construct()
    {
        $this->modelClass = Role::class;
    }

    protected function relations(): array
    {
        return [
            'permissions' => [
                'search_column' => 'id'
            ]
        ];
    }

    public function rolesPermissionsData()
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();

        return [
            'roles' => $roles,
            'permissions' => $permissions
        ];
    }

    public function getDownloadCols(): array
    {
        return [
            'id',
            'name'
        ];
    }

    public function permissionUpdate($roleId, $permissionId, $granted)
    {
        /**
         * @var Role
         */
        $role = Role::with('permissions')->where('id', $roleId)->get()->first();
        $existingPermissions = $role->permissions()->pluck('id')->toArray();
        switch($granted) {
            case 1:
                if (!in_array($permissionId, $existingPermissions)) {
                    $role->assignPermissions($permissionId);
                }
                break;
            case 0:
                if (in_array($permissionId, $existingPermissions)) {
                    $role->reomvePermissions($permissionId);
                }
                break;
        }
        return true;

    }

    public function getStoreValidationRules(): array
    {
        return [
            'name' => ['required', 'string'],
            'permissions' => ['required', 'array']
        ];
    }

    public function getUpdateValidationRules($id = null): array
    {
        return [
            'name' => ['required', 'string'],
            'permissions' => ['required', 'array']
        ];
    }
}

?>
