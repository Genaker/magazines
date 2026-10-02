<?php

namespace App\Jobs;

use App\Events\PostViewed;
use App\Models\Post;
use App\Models\PostView;
use App\Support\ExceptionLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RecordPostView implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $postId,
        public string $ipAddress,
        public ?int $userId = null,
        public ?string $userAgent = null,
    ) {}

    public function handle(): void
    {
        $today = now()->startOfDay();

        $alreadyViewed = PostView::query()
            ->where('post_id', $this->postId)
            ->where('ip_address', $this->ipAddress)
            ->whereDate('viewed_on', $today)
            ->exists();

        if ($alreadyViewed) {
            return;
        }

        try {
            PostView::query()->create([
                'post_id' => $this->postId,
                'ip_address' => $this->ipAddress,
                'viewed_on' => $today,
                'user_id' => $this->userId,
                'user_agent' => $this->userAgent,
                'created_at' => now(),
            ]);

            Post::query()->whereKey($this->postId)->increment('views_count');

            PostViewed::dispatch($this->postId, $this->ipAddress, $this->userId);
        } catch (UniqueConstraintViolationException) {
            return;
        } catch (Throwable $exception) {
            ExceptionLogger::log($exception, 'Failed to record post view', [
                'post_id' => $this->postId,
            ]);
        }
    }
}
