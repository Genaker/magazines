<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserActivity extends Notification
{
    use Queueable;

    public function __construct(
        public string $kind,
        public User $actor,
        public ?Post $post = null,
        public ?Comment $comment = null,
        public ?Magazine $magazine = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->post?->loadMissing('user');

        return [
            'kind' => $this->kind,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'actor_username' => $this->actor->username,
            'actor_avatar' => $this->actor->avatar,
            'post_id' => $this->post?->id,
            'post_title' => $this->post?->title,
            'post_slug' => $this->post?->slug,
            'post_author_username' => $this->post?->user?->username,
            'comment_id' => $this->comment?->id,
            'magazine_id' => $this->magazine?->id,
            'magazine_name' => $this->magazine?->name,
            'magazine_slug' => $this->magazine?->slug,
        ];
    }
}
