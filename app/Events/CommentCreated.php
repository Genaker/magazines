<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Foundation\Events\Dispatchable;

class CommentCreated
{
    use Dispatchable;

    public function __construct(public Comment $comment) {}
}
