<?php

namespace App\Services;

use App\Constants\MessPermission;
use App\Models\Mess;
use App\Models\MessUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    protected $cacheExpiration = 300; // 5 minutes

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

        $cacheKey = "permissions.user.{$user->id}.{$permission}";

        return Cache::remember($cacheKey, $this->cacheExpiration, function() use ($user, $permission) {
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
        });
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
     * Check if a model's attribute equals a given value
     *
     * @param Model $model
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function modelAttributeIs(Model $model, string $attribute, $value): bool
    {
        if (!isset($model->{$attribute})) {
            return false;
        }

        return $model->{$attribute} == $value;
    }

    /**
     * Check if a model has a specific attribute value and status
     *
     * @param Model $model The model to check
     * @param string $attribute The attribute name to check
     * @param mixed $value The expected value of the attribute
     * @param string|array $status The expected status value(s)
     * @param string $statusField The field name for status
     * @return bool
     */
    public function modelAttributeWithStatus(
        Model $model,
        string $attribute,
        $value,
        $status,
        string $statusField = 'status'
    ): bool {
        // Check if model has the attribute with the specified value
        if (!$this->modelAttributeIs($model, $attribute, $value)) {
            return false;
        }

        // Check if model has the required status
        return is_array($status)
            ? in_array($model->{$statusField}, $status)
            : $model->{$statusField} == $status;
    }

    /**
     * Check if two models belong to the same user
     *
     * @param Model $model1 First model to check
     * @param Model $model2 Second model to check
     * @param string $userIdField1 Field name for user_id in first model
     * @param string $userIdField2 Field name for user_id in second model
     * @return bool
     */
    public function modelsBelongToSameUser(
        Model $model1,
        Model $model2,
        string $userIdField1 = 'user_id',
        string $userIdField2 = 'user_id'
    ): bool {
        // Check if both models have the user_id attribute
        if (!isset($model1->{$userIdField1}) || !isset($model2->{$userIdField2})) {
            return false;
        }

        // Check if user_ids match and are not null
        return $model1->{$userIdField1} === $model2->{$userIdField2} &&
               $model1->{$userIdField1} !== null;
    }

    /**
     * Check if two models belong to the same mess user
     *
     * @param Model $model1 First model to check
     * @param Model $model2 Second model to check
     * @param string $messUserField1 Field name for mess_user_id in first model
     * @param string $messUserField2 Field name for mess_user_id in second model
     * @return bool
     */
    public function modelsBelongToSameMessUser(
        Model $model1,
        Model $model2,
        string $messUserField1 = 'mess_user_id',
        string $messUserField2 = 'mess_user_id'
    ): bool {
        // Check if both models have the mess_user_id attribute
        if (!isset($model1->{$messUserField1}) || !isset($model2->{$messUserField2})) {
            return false;
        }

        // Check if mess_user_ids match and are not null
        return $model1->{$messUserField1} === $model2->{$messUserField2} &&
               $model1->{$messUserField1} !== null;
    }

    /**
     * Check if two models belong to the same mess
     *
     * @param Model $model1 First model to check
     * @param Model $model2 Second model to check
     * @param string $messField1 Field name for mess_id in first model
     * @param string $messField2 Field name for mess_id in second model
     * @return bool
     */
    public function modelsBelongToSameMess(
        Model $model1,
        Model $model2,
        string $messField1 = 'mess_id',
        string $messField2 = 'mess_id'
    ): bool {
        // Check if both models have the mess_id attribute
        if (!isset($model1->{$messField1}) || !isset($model2->{$messField2})) {
            return false;
        }

        // Check if mess_ids match and are not null
        return $model1->{$messField1} === $model2->{$messField2} &&
               $model1->{$messField1} !== null;
    }

    /**
 * Check if a model belongs to the authenticated user
 *
 * @param Model $model Model to check
 * @param string $userIdField Field name for user_id in model
 * @return bool
 */
public function modelBelongsToAuthUser(
    Model $model,
    string $userIdField = 'user_id'
): bool {
    $user = auth()->user();

    // Check if there's an authenticated user and the model has the user_id attribute
    if (!$user || !isset($model->{$userIdField})) {
        return false;
    }

    // Check if user_id matches and is not null
    return $model->{$userIdField} === $user->id &&
           $model->{$userIdField} !== null;
}

/**
 * Check if a model belongs to the authenticated user's mess user
 *
 * @param Model $model Model to check
 * @param string $messUserIdField Field name for mess_user_id in model
 * @return bool
 */
public function modelBelongsToAuthMessUser(
    Model $model,
    string $messUserIdField = 'mess_user_id'
): bool {
    $user = auth()->user();

    // Check if there's an authenticated user with a mess user relationship and the model has the mess_user_id attribute
    if (!$user || !$user->messUser || !isset($model->{$messUserIdField})) {
        return false;
    }

    // Check if mess_user_id matches and is not null
    return $model->{$messUserIdField} === $user->messUser->id &&
           $model->{$messUserIdField} !== null;
}

/**
 * Check if a model belongs to the authenticated user's mess
 *
 * @param Model $model Model to check
 * @param string $messIdField Field name for mess_id in model
 * @return bool
 */
public function modelBelongsToAuthMess(
    Model $model,
    string $messIdField = 'mess_id'
): bool {
    $user = auth()->user();

    // Check if there's an authenticated user with a mess user relationship that has a mess and the model has the mess_id attribute
    if (!$user || !$user->messUser || !$user->messUser->mess || !isset($model->{$messIdField})) {
        return false;
    }

    // Check if mess_id matches and is not null
    return $model->{$messIdField} === $user->messUser->mess_id &&
           $model->{$messIdField} !== null;
}
}
