<?php

namespace App\Enums;

enum NotificationPriority: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function getIcon(): string
    {
        return match($this) {
            self::LOW => '📄',
            self::NORMAL => '📋',
            self::HIGH => '⚠️',
            self::URGENT => '🚨',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::LOW => '#6B7280',
            self::NORMAL => '#3B82F6',
            self::HIGH => '#F59E0B',
            self::URGENT => '#EF4444',
        };
    }
}
