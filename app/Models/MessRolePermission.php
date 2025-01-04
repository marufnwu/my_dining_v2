<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessRolePermission extends Model
{
    protected $fillable = [
        'mess_role_id', // Foreign key for MessRole
        'permission',   // Permission name
    ];
}
