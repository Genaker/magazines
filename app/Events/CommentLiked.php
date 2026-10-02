<?php

namespace App\Events;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class CommentLiked
{
    use Dispatchable;

    public function __construct(
        public Comment $comment,
        public User $liker,
    ) {}
}
