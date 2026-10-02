<?php

namespace App\Notifications;

use App\Models\Post;
use App\Support\SiteUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagazinePostSubmitted extends Notification implements ShouldQueue
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
        $author = $this->post->user;

        return (new MailMessage)
            ->subject(__('notification.magazine_post_submitted_subject', [
                'magazine' => $magazine->name,
                'title' => $this->post->title,
            ]))
            ->line(__('notification.magazine_post_submitted_line', [
                'name' => $author->name,
                'username' => $author->username,
                'magazine' => $magazine->name,
            ]))
            ->line($this->post->title)
            ->action(
                __('notification.review_submissions'),
                SiteUrl::mainRoute('magazines.submissions', $magazine),
            );
    }
}
