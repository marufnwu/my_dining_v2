<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessRole extends Model
{
    protected $fillable = [
        'mess_id', // Foreign key for Mess
        'role',    // Role name
        'is_default', // Default status
    ];

    /**
     * Get all of the permissions for the MessRole
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(MessRolePermission::class,);
    }
}
