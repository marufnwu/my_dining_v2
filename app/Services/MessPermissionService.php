<?php

namespace App\Services;

use App\Constants\MessPermission;
use App\Constants\MessUserRole;
use App\Models\Mess;

class MessPermissionService
{
    private Mess $mess;

    public function __construct(Mess $mess) {
        $this->mess = $mess;
    }

    function addMessDefaultRoleAndPermission(){

        $roles = config("mess.default_roles");

        foreach ($roles as $key => $role) {
            
        }

        return $this->mess->roles;

    }
}
