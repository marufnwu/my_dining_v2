<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessUser extends Model
{
    protected $fillable = [
        'mess_id',
        'user_id',
        'role',
        'joined_at',
        'left_at',
        'status',
    ];
    
    /**
     * Get the mess that owns the MessUser
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mess(): BelongsTo
    {
        return $this->belongsTo(Mess::class);
    }
}
