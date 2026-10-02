<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\Search\SearchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IndexPostForSearch implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $postId) {}

    public function handle(SearchService $search): void
    {
        if (config('search.driver') !== 'redis') {
            return;
        }

        $post = Post::query()
            ->with(['authorAlias', 'category', 'tags'])
            ->find($this->postId);

        if (! $post) {
            return;
        }

        if ($post->isPublished()) {
            $search->indexPost($post);
        } else {
            $search->removePost($post);
        }
    }
}
