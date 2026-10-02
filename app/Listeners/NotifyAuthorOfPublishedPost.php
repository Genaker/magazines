<?php

namespace App\Listeners;

use App\Events\PostPublished;
use App\Services\PostAuthorNotifier;

class NotifyAuthorOfPublishedPost
{
    public function handle(PostPublished $event): void
    {
        PostAuthorNotifier::published($event->post);
    }
}
