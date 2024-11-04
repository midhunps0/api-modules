<?php
namespace Modules\Ynotz\AccessControl\Traits;

use Modules\Ynotz\AccessControl\Models\Role;
use Modules\Ynotz\AccessControl\Models\Permission;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait WithRoles{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'users_roles', 'user_id', 'role_id');
    }

    /**
     * hasRole Checks if the user has the given role.
     *
     * @param integer|string|Role $role
     * @return boolean
     */
    public function hasRole(int|string|Role $role): bool
    {
        if (is_int($role)) {
            return in_array($role, array_values($this->roles()->pluck('id')->toArray()));
        } elseif (is_string($role)) {
            return in_array($role, array_values($this->roles()->pluck('name')->toArray()));
        } elseif ($role instanceof Role) {
            return in_array($role->id, array_values($this->roles()->pluck('id')->toArray()));
        }
    }

    public function permissions(): array
    {
        $permissions = [];
        foreach ($this->roles as $role) {
            array_push($permissions, ...($role->permissions));
        }
        return $permissions;
    }

    /**
     * hasPermissionTo check if the user has the give permission
     *
     * @param integer|string|Permission $permission
     * @return boolean
     */
    public function hasPermissionTo(int|string|Permission $permission): bool
    {
        if (is_int($permission)) {
            return in_array($permission, array_values(collect($this->permissions())->pluck('id')->toArray()));
        } elseif (is_string($permission)) {
            return in_array($permission, array_values(collect($this->permissions())->pluck('name')->toArray()));
        } elseif ($permission instanceof Permission) {
            return in_array($permission->id, array_values(collect($this->permissions())->pluck('id')->toArray()));
        }
    }

    /**
     * hasAnyPermission checks if the user has any of the given permissions
     *
     * @param array $permissions
     * @return boolean
     */
    public function hasAnyPermission(array $permissions): bool
    {
        $status = false;
        foreach ($permissions as $permission) {
            if ($this->hasPermissionTo($permission)) {
                $status = true;
                break;
            }
        }
        return $status;
    }

    /**
     * assignRole Assign the role to the user
     *
     * @param integer|string|Role $role
     * @return boolean
     */
    public function assignRole(int|string|Role $role): bool
    {
        if (is_int($role)) {
            $theRole = Role::find($role);
        } elseif (is_string($role)) {
            $theRole = Role::where('name', $role)->get()->first();
        } elseif ($role instanceof Role) {
            $theRole = $role;
        }
        if ($theRole == null) {
            return false;
        }
        $this->roles()->attach($theRole->id);
        return true;
    }

    /**
     * removeRole
     *
     * @param integer|string|Role $role
     * @return boolean
     */
    public function removeRole(int|string|Role $role): bool
    {
        if (is_int($role)) {
            $theRole = Role::find($role);
        } elseif (is_string($role)) {
            $theRole = Role::where('name', $role)->get()->first();
        } elseif ($role instanceof Role) {
            $theRole = $role;
        }
        if ($theRole == null) {
            return false;
        }
        $this->roles()->detach($theRole->id);
        return true;
    }

    public function scopeHavingRoles($query, $roles)
    {
        if (count($roles) > 0) {
            return $query;
        }
        if (is_int($roles[0])) {
            return $query->whereHas('roles', function ($q) use ($roles) {
                $q->whereIn('id', $roles);
            });
        }
        return $query->whereHas('roles', function ($q) use ($roles) {
            $q->whereIn('name', $roles);
        });
    }

    public function scopeExceptSelf(Builder $query)
    {
        $query->where('id', '<>', auth()->user()->id);
        return $query;
    }
}
