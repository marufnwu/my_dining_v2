<?php

namespace App\Facades;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool can(string $permission)
 * @method static bool canAll(array $permissions)
 * @method static bool canAny(array $permissions)
 * @method static bool hasPermission(?User $user, string $permission)
 * @method static bool hasAllPermissions(?User $user, array $permissions)
 * @method static bool hasAnyPermission(?User $user, array $permissions)
 * @method static bool modelAttributeIs(Model $model, string $attribute, mixed $value)
 * @method static bool modelBelongsToUser(Model $model, string $userIdField = 'user_id')
 * @method static bool modelBelongsTo(Model $model, User $user, string $userIdField = 'user_id')
 * @method static bool canAccessModel(string $permission, Model $model, string $userIdField = 'user_id')
 * @method static bool canAnyAccessModel(array $permissions, Model $model, string $userIdField = 'user_id')
 * @method static bool modelFieldsMatch(Model $model1, string $field1, Model $model2, string $field2)
 *
 * @see \App\Services\PermissionService
 */
class Permission extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'permission';
    }
}
