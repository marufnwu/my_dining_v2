<?php

namespace App\Models;

use App\Enums\MessUserStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessUser extends Model
{
    use \App\Traits\HasModelName;
    protected $fillable = [
        'mess_id',
        'user_id',
        'mess_role_id',
        'joined_at',
        'left_at',
        'status',
    ];

    /**
     * Get the user that owns the MessUser
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    function scopeByStatus(Builder $q, MessUserStatus $status) {
        return $q->where("status", $status->value);
    }


    /**
     * Get the mess that owns the MessUser
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mess(): BelongsTo
    {
        return $this->belongsTo(Mess::class);
    }

    /**
     * Get the role that owns the MessUser
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(MessRole::class);
    }
}
