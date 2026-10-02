<?php

namespace App\Support;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/** Sends notifications immediately or via Laravel's queue (config-driven). */
class NotificationSender
{
    public static function send(object $notifiable, Notification $notification): void
    {
        if (NotificationDelivery::shouldQueue($notification)) {
            self::applyMailQueue($notification);
            $notifiable->notify($notification);

            return;
        }

        $notifiable->notifyNow($notification);
    }

    /**
     * @param  iterable<int, object>  $notifiables
     */
    public static function sendToMany(iterable $notifiables, Notification $notification): void
    {
        if (NotificationDelivery::shouldQueue($notification)) {
            self::applyMailQueue($notification);
            NotificationFacade::send($notifiables, $notification);

            return;
        }

        NotificationFacade::sendNow($notifiables, $notification);
    }

    private static function applyMailQueue(Notification $notification): void
    {
        if (! in_array(Queueable::class, class_uses_recursive($notification), true)) {
            return;
        }

        $notification->onQueue((string) config('notifications.queue', 'mail'));
    }
}
