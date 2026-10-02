<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class PostViewed
{
    use Dispatchable;

    public function __construct(
        public int $postId,
        public string $ipAddress,
        public ?int $userId = null,
    ) {}
}
