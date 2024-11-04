<?php
namespace Modules\Ynotz\AccessControl\Services;

use App\Models\User;
use App\Services\RoleService;
use Illuminate\Support\Facades\Hash;
use Modules\Ynotz\AccessControl\Models\Role;
use Modules\Ynotz\EasyAdmin\Services\FormHelper;
use Modules\Ynotz\EasyAdmin\Services\IndexTable;
use Modules\Ynotz\AuditLog\Events\BusinessActionEvent;
use Modules\Ynotz\EasyAdmin\Traits\IsModelViewConnector;
use Modules\Ynotz\EasyAdmin\Contracts\ModelViewConnector;
use Modules\Ynotz\EasyAdmin\RenderDataFormats\CreatePageData;
use Modules\Ynotz\EasyAdmin\RenderDataFormats\EditPageData;
use Modules\Ynotz\EasyApi\Contracts\ApiCrudHelperContract;
use Modules\Ynotz\EasyApi\Traits\DefaultApiCrudHelper;

class UserService implements ApiCrudHelperContract {
    use DefaultApiCrudHelper;
    private $indexTable;

    public function __construct()
    {
        $this->modelClass = User::class;
    }

    protected function relations()
    {
        return [
            'roles' => [
                'search_column' => 'id',
                'filter_column' => 'id',
                'sort_column' => 'id',
            ],
            'district' => [
                'search_column' => 'id',
                'filter_column' => 'id',
                'sort_column' => 'id',
            ],
        ];
    }

    public function getDownloadCols(): array
    {
        return [
            'id',
            'name',
            'roles.name'
        ];
    }

    private function getQuery()
    {
        return $this->modelClass::query()->with([
            'roles' => function ($query) {
                $query->select('id', 'name');
            }
        ]);
    }

    public function getStoreValidationRules(): array
    {
        return [
            'name' => ['required', 'string'],
            // 'username' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'roles.*' => ['required'],

        ];
    }

    public function getUpdateValidationRules($id = null): array
    {
        $arr = $this->getStoreValidationRules();
        unset($arr['password']);
        return $arr;
    }

}

?>
