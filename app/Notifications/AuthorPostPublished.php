<?php

namespace App\Notifications;

use App\Models\Post;
use App\Support\PostUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AuthorPostPublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Post $post) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('notification.author_post_published_subject', ['title' => $this->post->title]))
            ->line(__('notification.author_post_published_line', ['title' => $this->post->title]));

        if ($this->post->magazine && $this->post->magazine_submission_status?->value === 'pending') {
            $message->line(__('notification.author_post_published_magazine_pending', [
                'magazine' => $this->post->magazine->name,
            ]));
        } elseif ($this->post->magazine && $this->post->magazine_submission_status?->value === 'approved') {
            $message->line(__('notification.author_post_published_magazine_approved', [
                'magazine' => $this->post->magazine->name,
            ]));
        }

        return $message->action(__('notification.read_story'), PostUrl::for($this->post));
    }
}
