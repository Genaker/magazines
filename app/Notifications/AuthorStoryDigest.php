<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class AuthorStoryDigest extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Collection<int, \App\Models\SubscriptionDigestItem>  $items
     */
    public function __construct(
        public Collection $items,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->items->loadMissing(['post.user']);

        $mail = (new MailMessage)
            ->subject('Your daily story digest')
            ->greeting('Stories from authors you follow')
            ->line('Here are new stories from writers you subscribed to:');

        foreach ($this->items as $item) {
            $post = $item->post;
            $author = $post->user;
            $url = route('posts.show', [$author->username, $post->slug]);

            $mail->line('• '.$post->title.' by '.$author->name)
                ->line($url);
        }

        return $mail->line('Manage subscriptions from each author\'s profile page.');
    }
}
