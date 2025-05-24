<?php

namespace App\Models;

use App\Constants\MessUserRole;
use App\Enums\MessStatus;
use App\Enums\MessUserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Mess extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'status',
        'ad_free',
        'all_user_add_meal',
        'fund_add_enabled',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => MessStatus::class,
        'ad_free' => "boolean",
        'all_user_add_meal' => 'boolean',
        'fund_add_enabled' => 'boolean',
    ];

    /**
     * Get the users associated with the mess.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messUsers(): HasMany
    {
        return $this->hasMany(MessUser::class)->with("user", "role.permissions");
    }


    /**
     * Get all of the roles for the Mess
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function roles(): HasMany
    {
        return $this->hasMany(MessRole::class);
    }

    public function adminRole(): HasOne
    {
        return $this->hasOne(MessRole::class)->where('role', MessUserRole::ADMIN);
    }

    public function managerRole(): HasOne
    {
        return $this->hasOne(MessRole::class)->where('role', MessUserRole::MANAGER);
    }

    public function memberRole(): HasOne
    {
        return $this->hasOne(MessRole::class)->where('role', MessUserRole::MEMBER);
    }

    /**
     * Get all of the months for the Mess
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function months(): HasMany
    {
        return $this->hasMany(Month::class);
    }

    public function activeMonth(): HasOne
    {
        return $this->hasOne(Month::class)
            ->where(function ($query) {
                $query->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            });
    }

    /**
     * Get the subscriptions for the mess.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the active subscription for the mess.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where(function ($query) {
                $query->where('status', Subscription::STATUS_ACTIVE)
                    ->orWhere('status', Subscription::STATUS_TRIAL);
            })
            ->where('expires_at', '>', now())
            ->latest();
    }

    /**
     * Check if mess has an active subscription.
     *
     * @return bool
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    /**
     * Check if mess has access to a feature.
     *
     * @param string $featureName
     * @return bool
     */
    public function hasFeatureAccess($featureName): bool
    {
        $subscription = $this->activeSubscription;

        if (!$subscription) {
            return false;
        }

        $feature = $subscription->plan->features()
            ->where('name', $featureName)
            ->first();

        if (!$feature) {
            return false;
        }

        if (!$feature->is_countable) {
            return true;
        }

        $usage = $subscription->featureUsages()
            ->where('plan_feature_id', $feature->id)
            ->first();

        return $usage && $usage->withinLimits();
    }

    /**
     * Use a feature and record its usage.
     *
     * @param string $featureName
     * @param int $amount
     * @return bool
     */
    public function useFeature($featureName, $amount = 1): bool
    {
        return app('App\Services\SubscriptionService')->recordFeatureUsage($this, $featureName, $amount);
    }

    /**
     * Get all orders associated with this mess.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(SubscriptionOrder::class);
    }

    /**
     * Get all transactions associated with this mess.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get all invoices associated with this mess.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get total spent on subscriptions.
     *
     * @return float
     */
    public function getTotalSpentAttribute(): float
    {
        return $this->transactions()
            ->where('status', Transaction::STATUS_COMPLETED)
            ->sum('amount');
    }
}
