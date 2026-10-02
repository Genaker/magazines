<?php

namespace App\Support;

use App\Jobs\RecordPostView;

/** Records post views in-process after the response or via Laravel's queue. */
class PostViewRecorder
{
    /** Whether views are dispatched to the default queue connection. */
    public static function usesQueue(): bool
    {
        return config('post-views.count_mode') === 'async';
    }

    public static function isAsync(): bool
    {
        return self::usesQueue();
    }

    /**
     * Record a single post view without blocking the HTTP response.
     *
     * sync  — run after the response in-process (no queue)
     * async — dispatch RecordPostView to config('queue.default') after the response
     */
    public static function record(
        int $postId,
        string $ipAddress,
        ?int $userId = null,
        ?string $userAgent = null,
    ): void {
        $job = new RecordPostView($postId, $ipAddress, $userId, $userAgent);

        if (self::usesQueue()) {
            dispatch($job)
                ->onConnection((string) config('queue.default'))
                ->afterResponse();

            return;
        }

        defer(fn () => $job->handle());
    }
}
