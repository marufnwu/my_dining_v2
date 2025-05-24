<?php

namespace App\Facades;

use App\Models\User;
use App\Models\MessUser;
use App\Models\Mess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool can(string $permission)
 * @method static bool canAny(array $permissions)
 * @method static bool hasPermission(?User $user, string $permission)
 * @method static bool hasAnyPermission(?User $user, array $permissions)
 *
 * @method static bool modelAttributeIs(Model $model, string $attribute, mixed $value)
 * @method static bool modelAttributeWithStatus(Model $model, string $attribute, mixed $value, string|array $status, string $statusField = 'status')
 *
 * @method static bool modelsBelongToSameUser(Model $model1, Model $model2, string $userIdField1 = 'user_id', string $userIdField2 = 'user_id')
 * @method static bool modelsBelongToSameMessUser(Model $model1, Model $model2, string $messUserField1 = 'mess_user_id', string $messUserField2 = 'mess_user_id')
 * @method static bool modelsBelongToSameMess(Model $model1, Model $model2, string $messField1 = 'mess_id', string $messField2 = 'mess_id')
 *
 * @method static bool modelBelongsToAuthUser(Model $model, string $userIdField = 'user_id')
 * @method static bool modelBelongsToAuthMessUser(Model $model, string $messUserIdField = 'mess_user_id')
 * @method static bool modelBelongsToAuthMess(Model $model, string $messIdField = 'mess_id')
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
