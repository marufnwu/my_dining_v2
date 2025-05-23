<?php

namespace App\Services;

use App\Constants\MessPermission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PermissionService
{
    /**
     * Check if the authenticated user has a specific permission
     *
     * @param string $permission
     * @return bool
     */
    public function can(string $permission): bool
    {
        return $this->hasPermission(auth()->user(), $permission);
    }

    /**
     * Check if the authenticated user has all specified permissions
     *
     * @param array $permissions
     * @return bool
     */
    public function canAll(array $permissions): bool
    {
        return $this->hasAllPermissions(auth()->user(), $permissions);
    }

    /**
     * Check if the authenticated user has any of the specified permissions
     *
     * @param array $permissions
     * @return bool
     */
    public function canAny(array $permissions): bool
    {
        return $this->hasAnyPermission(auth()->user(), $permissions);
    }

    /**
     * Check if the user has a specific permission
     *
     * @param User|null $user
     * @param string $permission
     * @return bool
     */
    public function hasPermission(?User $user, string $permission): bool
    {
        if (!$user) {
            return false;
        }

        $role = $user->role;

        if (!$role) {
            return false;
        }

        // Admin roles have all permissions
        if ($role->is_admin) {
            return true;
        }

        // Check if the user's role has the specific permission
        return $role->permissions()->where('permission', $permission)->exists();
    }

    /**
     * Check if the user has all specified permissions
     *
     * @param User|null $user
     * @param array $permissions
     * @return bool
     */
    public function hasAllPermissions(?User $user, array $permissions): bool
    {
        if (!$user || empty($permissions)) {
            return false;
        }

        $role = $user->role;

        if (!$role) {
            return false;
        }

        // Admin roles have all permissions
        if ($role->is_admin) {
            return true;
        }

        // Get count of matching permissions
        $permissionCount = $role->permissions()
            ->whereIn('permission', $permissions)
            ->count();

        // All specified permissions must be present
        return $permissionCount === count($permissions);
    }

    /**
     * Check if the user has any of the specified permissions
     *
     * @param User|null $user
     * @param array $permissions
     * @return bool
     */
    public function hasAnyPermission(?User $user, array $permissions): bool
    {
        if (!$user || empty($permissions)) {
            return false;
        }

        $role = $user->role;

        if (!$role) {
            return false;
        }

        // Admin roles have all permissions
        if ($role->is_admin) {
            return true;
        }

        // Check if any of the specified permissions exists
        return $role->permissions()
            ->whereIn('permission', $permissions)
            ->exists();
    }

    /**
     * Check if a model's attribute matches a given value
     *
     * @param Model $model
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function modelAttributeIs(Model $model, string $attribute, $value): bool
    {
        return $model->{$attribute} == $value;
    }

    /**
     * Check if a model belongs to the authenticated user
     *
     * @param Model $model
     * @param string $userIdField
     * @return bool
     */
    public function modelBelongsToUser(Model $model): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        return $this->modelAttributeIs($model, "user_id", $user->id);
    }

    /**
     * Check if a model belongs to the authenticated user
     *
     * @param Model $model
     * @param string $userIdField
     * @return bool
     */
    public function modelBelongsToMessUser(Model $model): bool
    {
        $user = auth()->user()?->messUser;

        if (!$user) {
            return false;
        }

        return $this->modelAttributeIs($model, "mess_user_id", $user->id);
    }

    /**
     * Check if a model belongs to a specific user
     *
     * @param Model $model
     * @param User $user
     * @param string $userIdField
     * @return bool
     */
    public function modelBelongsTo(Model $model, User $user, string $userIdField = 'user_id'): bool
    {
        return $this->modelAttributeIs($model, $userIdField, $user->id);
    }

    /**
     * Check if the authenticated user has permission and model belongs to them
     *
     * @param string $permission
     * @param Model $model
     * @param string $userIdField
     * @return bool
     */
    public function canAccessModel(string $permission, Model $model, string $userIdField = 'user_id'): bool
    {
        return $this->can($permission) && $this->modelBelongsToUser($model, $userIdField);
    }

    /**
     * Check if the authenticated user has any permission and model belongs to them
     *
     * @param array $permissions
     * @param Model $model
     * @param string $userIdField
     * @return bool
     */
    public function canAnyAccessModel(array $permissions, Model $model, string $userIdField = 'user_id'): bool
    {
        return $this->canAny($permissions) && $this->modelBelongsToUser($model, $userIdField);
    }

    /**
     * Check if a specific field matches between two models
     *
     * @param Model $model1
     * @param string $field1
     * @param Model $model2
     * @param string $field2
     * @return bool
     */
    public function modelFieldsMatch(Model $model1, string $field1, Model $model2, string $field2): bool
    {
        return $model1->{$field1} == $model2->{$field2};
    }
}
