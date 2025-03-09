<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\MessUserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, \App\Traits\HasModelName;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'user_name',
        'email',
        'country_id',
        'phone',
        'gender',
        'city',
        'password',
        'status',
        'join_date',
        'leave_date',
        'photo_url',
        'fcm_token',
        'version',
        'last_active',
    ];

    protected $appends = [
        'is_email_verified',
    ];


    /**
     * Get the mess that the user is currently associated with.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
    public function role(): HasOneThrough
    {
        return $this->hasOneThrough(
            MessRole::class,     // The final model (MessRole)
            MessUser::class,      // The intermediate model (MessUser)
            'user_id',            // Foreign key on MessUser (points to User)
            'id',                 // Foreign key on MessRole (points to MessUser)
            'id',                 // Local key on User
            'mess_role_id'        // Local key on MessUser (points to MessRole)
        )
            ->whereNull("left_at")    // Filter users who haven't left
            ->latest()                // Get the latest entry
            ->withDefault(null);      // Return null if no relationship exists
    }


    /**
     * Get the mess that the user is currently associated with.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
    public function activeMess(): HasOneThrough
    {
        return $this->hasOneThrough(
            Mess::class,     // The final model (Mess)
            MessUser::class, // The intermediate model (MessUser)
            'user_id',       // Foreign key on MessUser
            'id',            // Foreign key on Mess
            'id',            // Local key on User
            'mess_id'        // Local key on MessUser
        )
            ->where("mess_users.status", MessUserStatus::Active->value)
            ->whereNull("left_at")
            ->latest()
            ->withDefault(null);
    }

    /**
     * Get the messInfo associated with the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function messUser(): HasOne
    {
        return $this->hasOne(MessUser::class, 'user_id', 'id')
            // ->with("mess")
            ->whereNull("left_at")
            ->latest()
            ->withDefault(null);
    }



    function getIsEmailVerifiedAttribute()
    {
        return $this->isEmailVerified();
    }


    function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            "is_email_verified" => 'boolean'
        ];
    }
}
