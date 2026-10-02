<?php

namespace App\Support;

use Illuminate\Notifications\Notification;

/** Decides whether outbound notifications should be queued. */
class NotificationDelivery
{
    /** Whether outbound email notifications use the queue driver. */
    public static function usesQueue(): bool
    {
        return config('notifications.email_delivery') === 'queue';
    }

    /** Queue unless delivery is sync or the notification is on the sync allowlist. */
    public static function shouldQueue(Notification $notification): bool
    {
        if (! self::usesQueue()) {
            return false;
        }

        return ! in_array($notification::class, config('notifications.sync_notifications', []), true);
    }
}
