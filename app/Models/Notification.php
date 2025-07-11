<?php

namespace App\Models;

use App\Enums\NotificationCategory;
use App\Enums\NotificationPriority;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'mess_id',
        'user_id',
        'title',
        'body',
        'type',
        'category',
        'priority',
        'data',
        'action_data',
        'is_broadcast',
        'is_actionable',
        'is_dismissible',
        'icon',
        'color',
        'image_url',
        'read_at',
        'expires_at',
        'scheduled_at',
        'source',
        'delivery_channels',
        'fcm_response',
        'is_delivered',
        'delivered_at',
        'retry_count',
        // New advanced fields
        'tags',
        'notification_group_id',
        'group_key',
        'collapse_key',
        'thread_id',
        'locale',
        'translations',
        'ab_test_group',
        'ab_test_id',
        'tracking_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'click_count',
        'open_count',
        'conversion_count',
        'last_clicked_at',
        'last_opened_at',
        'batch_id',
        'batch_size',
        'batch_index',
        'is_test',
        'recipient_timezone',
        'optimal_send_time',
        'frequency_cap',
        'last_sent_at',
        'send_count',
        'engagement_score',
        'personalization_data',
        'device_targeting',
        'geo_targeting',
        'behavior_triggers',
        'template_id',
        'template_version'
    ];

    protected $casts = [
        'data' => 'array',
        'action_data' => 'array',
        'is_broadcast' => 'boolean',
        'is_actionable' => 'boolean',
        'is_dismissible' => 'boolean',
        'read_at' => 'datetime',
        'expires_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'delivery_channels' => 'array',
        'fcm_response' => 'array',
        'is_delivered' => 'boolean',
        'delivered_at' => 'datetime',
        // New advanced casts
        'tags' => 'array',
        'translations' => 'array',
        'last_clicked_at' => 'datetime',
        'last_opened_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'personalization_data' => 'array',
        'device_targeting' => 'array',
        'geo_targeting' => 'array',
        'behavior_triggers' => 'array',
        'is_test' => 'boolean',
        'optimal_send_time' => 'datetime',
        'engagement_score' => 'decimal:2',
        'category' => NotificationCategory::class,
        'priority' => NotificationPriority::class,
    ];

    protected $appends = [
        'is_read',
        'is_expired',
        'time_ago',
        'formatted_priority',
        'category_info'
    ];

    public function mess(): BelongsTo
    {
        return $this->belongsTo(Mess::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhere('is_broadcast', true);
        });
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeByCategory(Builder $query, NotificationCategory $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeByPriority(Builder $query, NotificationPriority $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    public function scopeActionable(Builder $query): Builder
    {
        return $query->where('is_actionable', true);
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->whereNotNull('scheduled_at');
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->where('scheduled_at', '<=', now());
    }

    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->whereIn('priority', [
            NotificationPriority::HIGH,
            NotificationPriority::URGENT
        ]);
    }

    /**
     * Scope to filter notifications by tags
     */
    public function scopeWithTags($query, array $tags)
    {
        return $query->where(function ($q) use ($tags) {
            foreach ($tags as $tag) {
                $q->orWhereJsonContains('tags', $tag);
            }
        });
    }

    /**
     * Scope to filter notifications by priority
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to filter active notifications (not expired)
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope to filter scheduled notifications
     */
    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_at')
                    ->where('scheduled_at', '>', now());
    }

    // Accessors
    public function getIsReadAttribute(): bool
    {
        return $this->read_at !== null;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getFormattedPriorityAttribute(): array
    {
        return [
            'value' => $this->priority,
            'label' => ucfirst($this->priority->value),
            'color' => $this->priority->getColor(),
            'icon' => $this->priority->getIcon(),
        ];
    }

    public function getCategoryInfoAttribute(): array
    {
        return [
            'value' => $this->category,
            'label' => $this->category->getDisplayName(),
            'icon' => $this->category->getIcon(),
        ];
    }

    // Methods
    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    public function markAsUnread(): void
    {
        $this->update(['read_at' => null]);
    }

    public function markAsDelivered(array $fcmResponse = []): void
    {
        $this->update([
            'is_delivered' => true,
            'delivered_at' => now(),
            'fcm_response' => $fcmResponse
        ]);
    }

    public function incrementRetryCount(): void
    {
        $this->increment('retry_count');
    }

    public function canRetry(): bool
    {
        return $this->retry_count < 3;
    }

    public function shouldExpire(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isDue(): bool
    {
        return $this->scheduled_at && $this->scheduled_at->isPast();
    }

    public function hasActions(): bool
    {
        return $this->is_actionable && !empty($this->action_data);
    }

    public function getDeepLink(): ?string
    {
        return $this->action_data['deep_link'] ?? null;
    }

    public function getActionButtons(): array
    {
        if (!$this->hasActions()) {
            return [];
        }

        $buttons = [];
        foreach ($this->action_data as $key => $value) {
            if ($key !== 'deep_link' && is_string($value)) {
                $buttons[] = [
                    'key' => $key,
                    'label' => $value,
                ];
            }
        }

        return $buttons;
    }

    /**
     * Check if notification has been clicked
     */
    public function hasBeenClicked(): bool
    {
        return !is_null($this->last_clicked_at);
    }

    /**
     * Check if notification has been opened
     */
    public function hasBeenOpened(): bool
    {
        return !is_null($this->last_opened_at);
    }

    /**
     * Check if notification is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get translated content for current locale
     */
    public function getTranslatedContent(string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();

        if (isset($this->translations[$locale])) {
            return array_merge([
                'title' => $this->title,
                'content' => $this->content
            ], $this->translations[$locale]);
        }

        return [
            'title' => $this->title,
            'content' => $this->content
        ];
    }

    /**
     * Add a tag to the notification
     */
    public function addTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->tags = $tags;
        }
    }

    /**
     * Remove a tag from the notification
     */
    public function removeTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        $this->tags = array_values(array_filter($tags, fn($t) => $t !== $tag));
    }

    /**
     * Check if notification has a specific tag
     */
    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? []);
    }

    /**
     * Update engagement metrics
     */
    public function updateEngagementScore(): void
    {
        $score = 0;

        // Base score for delivery
        if ($this->is_delivered) {
            $score += 10;
        }

        // Score for reading
        if ($this->read_at) {
            $score += 30;
        }

        // Score for clicking
        if ($this->last_clicked_at) {
            $score += 50;
        }

        // Score for opening
        if ($this->last_opened_at) {
            $score += 40;
        }

        // Bonus for quick engagement (within 24 hours)
        if ($this->read_at && $this->read_at->diffInHours($this->created_at) < 24) {
            $score += 10;
        }

        $this->engagement_score = min($score, 100);
        $this->save();
    }
}
