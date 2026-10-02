<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class AuthorStoryPublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Post $post,
        public User $author,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('posts.show', [$this->author->username, $this->post->slug]);
        $unsubscribe = URL::signedRoute('users.subscribe.unsubscribe', [
            'subscriber' => $notifiable->id,
            'author' => $this->author->id,
        ]);

        return (new MailMessage)
            ->subject($this->author->name.' published a new story')
            ->greeting('New story from '.$this->author->name)
            ->line($this->post->title)
            ->when(filled($this->post->subtitle), fn (MailMessage $mail) => $mail->line($this->post->subtitle))
            ->action('Read story', $url)
            ->line('You receive this because you subscribed to '.$this->author->name.'.')
            ->line('Unsubscribe: '.$unsubscribe);
    }
}
