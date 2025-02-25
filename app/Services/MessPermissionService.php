<?php

namespace App\Services;

use App\Constants\MessPermission;
use App\Constants\MessUserRole;
use App\Models\Mess;

class MessPermissionService
{
    private Mess $mess;

    public function __construct(Mess $mess)
    {
        $this->mess = $mess;
    }

    function addMessDefaultRoleAndPermission()
    {

        $roles = config("mess.default_roles");

        foreach ($roles as $key => $role) {
            $messRole = $this->mess->roles()->create(
                [
                    'role' => $role['role'],
                    'is_default' => true,
                    "is_admin"=>$role['is_admin'] ?? false
                ]
            );

            if(isset($role['permissions'])){
                $permissions = collect($role['permissions'])->map(function ($value) {
                    return ['permission' => $value];
                })->toArray();

                if(!empty($permissions)){
                    $messRole->permissions()->createMany($permissions);
                }
            }


        }

        return $this->mess->roles;
    }
}
