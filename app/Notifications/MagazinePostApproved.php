<?php

namespace App\Notifications;

use App\Models\Post;
use App\Support\PostUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagazinePostApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Post $post) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $magazine = $this->post->magazine;

        return (new MailMessage)
            ->subject(__('notification.magazine_post_approved_subject', [
                'magazine' => $magazine->name,
                'title' => $this->post->title,
            ]))
            ->line(__('notification.magazine_post_approved_line', ['magazine' => $magazine->name]))
            ->line($this->post->title)
            ->action(__('notification.read_story'), PostUrl::canonical($this->post));
    }
}
