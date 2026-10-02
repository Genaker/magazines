<?php

namespace App\Support;

use App\Models\AuthorMonthlyStat;
use App\Models\Post;
use App\Models\PostMonthlyStat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Computes and persists monthly author/post stat snapshots. */
class AuthorStatsSnapshot
{
    /** Upsert author and per-post metrics for a calendar month. */
    public static function snapshotMonth(User $user, int $year, int $month): AuthorMonthlyStat
    {
        $metrics = self::computeMonthMetrics($user, $year, $month);

        $authorStat = AuthorMonthlyStat::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'year' => $year,
                'month' => $month,
            ],
            [
                'views' => $metrics['views'],
                'likes' => $metrics['likes'],
                'comments' => $metrics['comments'],
                'saves' => $metrics['saves'],
                'followers_gained' => $metrics['followers_gained'],
                'subscribers_gained' => $metrics['subscribers_gained'],
                'followers_total' => $metrics['followers_total'],
                'subscribers_total' => $metrics['subscribers_total'],
            ],
        );

        foreach ($metrics['posts'] as $row) {
            PostMonthlyStat::query()->updateOrCreate(
                [
                    'post_id' => $row['post_id'],
                    'year' => $year,
                    'month' => $month,
                ],
                [
                    'user_id' => $user->id,
                    'views' => $row['views'],
                    'likes' => $row['likes'],
                    'comments' => $row['comments'],
                    'saves' => $row['saves'],
                ],
            );
        }

        return $authorStat;
    }

    /** Snapshot every author who has at least one post. Returns the number processed. */
    public static function snapshotAllAuthors(int $year, int $month): int
    {
        $count = 0;

        User::query()
            ->whereHas('posts')
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($year, $month, &$count): void {
                foreach ($users as $user) {
                    self::snapshotMonth($user, $year, $month);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * @return array{
     *     views: int,
     *     likes: int,
     *     comments: int,
     *     saves: int,
     *     followers_gained: int,
     *     subscribers_gained: int,
     *     followers_total: int,
     *     subscribers_total: int,
     *     posts: Collection<int, array{post_id: int, views: int, likes: int, comments: int, saves: int}>
     * }
     */
    public static function computeMonthMetrics(User $user, int $year, int $month): array
    {
        [$start, $end] = self::monthRange($year, $month);
        $postIds = Post::query()->where('user_id', $user->id)->pluck('id');

        if ($postIds->isEmpty()) {
            return [
                'views' => 0,
                'likes' => 0,
                'comments' => 0,
                'saves' => 0,
                'followers_gained' => self::followersGained($user, $start, $end),
                'subscribers_gained' => self::subscribersGained($user, $start, $end),
                'followers_total' => self::followersTotalAt($user, $end),
                'subscribers_total' => self::subscribersTotalAt($user, $end),
                'posts' => collect(),
            ];
        }

        $viewsByPost = DB::table('post_views')
            ->select('post_id', DB::raw('COUNT(*) as total'))
            ->whereIn('post_id', $postIds)
            ->whereBetween('viewed_on', [$start->toDateString(), $end->toDateString()])
            ->groupBy('post_id')
            ->pluck('total', 'post_id');

        $likesByPost = DB::table('post_likes')
            ->select('post_id', DB::raw('COUNT(*) as total'))
            ->whereIn('post_id', $postIds)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('post_id')
            ->pluck('total', 'post_id');

        $commentsByPost = DB::table('comments')
            ->select('post_id', DB::raw('COUNT(*) as total'))
            ->whereIn('post_id', $postIds)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('post_id')
            ->pluck('total', 'post_id');

        $savesByPost = DB::table('reading_list_post')
            ->select('post_id', DB::raw('COUNT(*) as total'))
            ->whereIn('post_id', $postIds)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('post_id')
            ->pluck('total', 'post_id');

        $activePostIds = $postIds
            ->merge($viewsByPost->keys())
            ->merge($likesByPost->keys())
            ->merge($commentsByPost->keys())
            ->merge($savesByPost->keys())
            ->unique()
            ->values();

        $posts = $activePostIds->map(function (int $postId) use ($viewsByPost, $likesByPost, $commentsByPost, $savesByPost) {
            return [
                'post_id' => $postId,
                'views' => (int) ($viewsByPost[$postId] ?? 0),
                'likes' => (int) ($likesByPost[$postId] ?? 0),
                'comments' => (int) ($commentsByPost[$postId] ?? 0),
                'saves' => (int) ($savesByPost[$postId] ?? 0),
            ];
        })->sortByDesc('views')->values();

        return [
            'views' => (int) $viewsByPost->sum(),
            'likes' => (int) $likesByPost->sum(),
            'comments' => (int) $commentsByPost->sum(),
            'saves' => (int) $savesByPost->sum(),
            'followers_gained' => self::followersGained($user, $start, $end),
            'subscribers_gained' => self::subscribersGained($user, $start, $end),
            'followers_total' => self::followersTotalAt($user, $end),
            'subscribers_total' => self::subscribersTotalAt($user, $end),
            'posts' => $posts,
        ];
    }

    /**
     * Inclusive UTC start/end instants for a calendar month.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function monthRange(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1, 0, 0, 0, 'UTC')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return [$start, $end];
    }

    /** Count new followers gained within the month window. */
    private static function followersGained(User $user, Carbon $start, Carbon $end): int
    {
        return (int) DB::table('follows')
            ->where('following_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    /** Count new story subscribers gained within the month window. */
    private static function subscribersGained(User $user, Carbon $start, Carbon $end): int
    {
        return (int) DB::table('author_subscriptions')
            ->where('author_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    /** Total followers as of the end of the month. */
    private static function followersTotalAt(User $user, Carbon $end): int
    {
        return (int) DB::table('follows')
            ->where('following_id', $user->id)
            ->where('created_at', '<=', $end)
            ->count();
    }

    /** Total story subscribers as of the end of the month. */
    private static function subscribersTotalAt(User $user, Carbon $end): int
    {
        return (int) DB::table('author_subscriptions')
            ->where('author_id', $user->id)
            ->where('created_at', '<=', $end)
            ->count();
    }
}
