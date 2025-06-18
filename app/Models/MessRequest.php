<?php

namespace App\Models;

use App\Enums\MessJoinRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessRequest extends Model
{
    use HasFactory, \App\Traits\HasModelName;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_name',
        'old_user_id',
        'new_user_id',
        'request_date',
        'accept_date',
        'old_mess_id',
        'new_mess_id',
        'accept_by',
        'status',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_date' => 'datetime',
        'accept_date' => 'datetime',
        'old_user_id' => 'integer',
        'new_user_id' => 'integer',
        'old_mess_id' => 'integer',
        'new_mess_id' => 'integer',
        'accept_by' => 'integer',
        'status' => MessJoinRequestStatus::class,
    ];

    /**
     * Get the user making the request
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'old_user_id');
    }

    /**
     * Get the new user (if request is approved)
     */
    public function newUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'new_user_id');
    }

    /**
     * Get the old mess being left
     */
    public function oldMess(): BelongsTo
    {
        return $this->belongsTo(Mess::class, 'old_mess_id');
    }

    /**
     * Get the new mess being joined
     */
    public function newMess(): BelongsTo
    {
        return $this->belongsTo(Mess::class, 'new_mess_id');
    }

    /**
     * Get the user who accepted/rejected the request
     */
    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accept_by');
    }
}
