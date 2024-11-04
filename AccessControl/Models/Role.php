<?php

namespace Modules\Ynotz\AccessControl\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Ynotz\AccessControl\Models\Permission;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'roles_permissions',
            'role_id',
            'permission_id'
        );
    }

    public function hasPermission($permissionName)
    {
        return in_array(
            $permissionName,
            array_values($this->permissions()->pluck('name')->toArray())
        );
    }

    public function assignPermissions(...$permissions)
    {
        foreach ($permissions as $p) {
            if (is_int($p)) {
                $permission = Permission::find($p);
            } else if (is_string($p)) {
                $permission = Permission::where('name', $p)->get()->first();
            }
            if (isset($permission)) {
                $this->permissions()->attach($permission);
            }
        }
    }

    public function reomvePermissions(...$permissions)
    {
        foreach ($permissions as $p) {
            if (is_int($p)) {
                $permission = Permission::find($p);
            } else if (is_string($p)) {
                $permission = Permission::where('name', $p)->get()->first();
            }
            $this->permissions()->detach($permission);
        }
    }
}
