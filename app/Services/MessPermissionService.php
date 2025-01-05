<?php

namespace App\Services;

use App\Enums\MessPermission;
use App\Enums\MessUserRole;
use App\Models\Mess;

class MessPermissionService
{
    private Mess $mess;

    public function __construct(Mess $mess) {
        $this->mess = $mess;
    }

    function addMessDefaultRoleAndPermission(){

        if (!$this->mess->adminRole) {
            $this->mess->roles()->create([
                'role' => MessUserRole::Admin->value,
                "is_default" => true
            ])->permissions()->create([
                'permission' => MessPermission::ADMIN->value
            ]);
        }

        if (!$this->mess->managerRole) {
            $this->mess->roles()->create([
                'role' => MessUserRole::Manager->value,
                "is_default" => true
            ])->permissions()->create([
                'permission' => MessPermission::MANAGER->value
            ]);
        }

        if (!$this->mess->memberRole) {
            $this->mess->roles()->create([
                'role' => MessUserRole::Member->value,
                "is_default" => true
            ])->permissions()->create([
                'permission' => MessPermission::MEMBER->value
            ]);
        }

        return $this->mess->roles;

    }
}
