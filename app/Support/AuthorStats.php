<?php

namespace App\Support;

use App\Enums\PostStatus;
use App\Models\AuthorMonthlyStat;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostMonthlyStat;
use App\Models\PostView;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Aggregate author profile and monthly analytics metrics. */
class AuthorStats
{
    /**
     * @return array{
     *     followers: int,
     *     following: int,
     *     subscribers: int,
     *     published_posts: int,
     *     draft_posts: int,
     *     unlisted_posts: int,
     *     total_views: int,
     *     total_likes: int,
     *     total_comments: int,
     *     total_saves: int,
     *     views_last_30_days: int,
     *     posts: Collection<int, Post>
     * }
     */
    public static function forUser(User $user): array
    {
        $postIds = Post::query()->where('user_id', $user->id)->pluck('id');

        $posts = Post::query()
            ->where('user_id', $user->id)
            ->with(['authorAlias'])
            ->withCount(['comments', 'likes'])
            ->orderByDesc('views_count')
            ->orderByDesc('published_at')
            ->get();

        return [
            'followers' => $user->followers()->count(),
            'following' => $user->following()->count(),
            'subscribers' => $user->storySubscribers()->count(),
            'published_posts' => $posts->where('status', PostStatus::Published)->count(),
            'draft_posts' => $posts->where('status', PostStatus::Draft)->count(),
            'unlisted_posts' => $posts->where('status', PostStatus::Unlisted)->count(),
            'total_views' => (int) $posts->sum('views_count'),
            'total_likes' => (int) $posts->sum('likes_count'),
            'total_comments' => $postIds->isEmpty()
                ? 0
                : Comment::query()->whereIn('post_id', $postIds)->count(),
            'total_saves' => $postIds->isEmpty()
                ? 0
                : (int) DB::table('reading_list_post')->whereIn('post_id', $postIds)->count(),
            'views_last_30_days' => $postIds->isEmpty()
                ? 0
                : PostView::query()
                    ->whereIn('post_id', $postIds)
                    ->where('viewed_on', '>=', now()->subDays(30)->toDateString())
                    ->count(),
            'posts' => $posts,
        ];
    }

    /**
     * @return array{
     *     year: int,
     *     month: int,
     *     label: string,
     *     range_label: string,
     *     views: int,
     *     likes: int,
     *     comments: int,
     *     saves: int,
     *     followers_gained: int,
     *     subscribers_gained: int,
     *     posts: Collection<int, array{post: Post, views: int, likes: int, comments: int, saves: int}>
     * }
     */
    public static function forUserMonth(User $user, int $year, int $month): array
    {
        [$start, $end] = AuthorStatsSnapshot::monthRange($year, $month);
        $isCurrentMonth = now('UTC')->year === $year && now('UTC')->month === $month;

        if ($isCurrentMonth) {
            $metrics = AuthorStatsSnapshot::computeMonthMetrics($user, $year, $month);
        } else {
            $snapshot = AuthorMonthlyStat::query()
                ->where('user_id', $user->id)
                ->where('year', $year)
                ->where('month', $month)
                ->first();

            if ($snapshot) {
                $metrics = [
                    'views' => $snapshot->views,
                    'likes' => $snapshot->likes,
                    'comments' => $snapshot->comments,
                    'saves' => $snapshot->saves,
                    'followers_gained' => $snapshot->followers_gained,
                    'subscribers_gained' => $snapshot->subscribers_gained,
                    'posts' => PostMonthlyStat::query()
                        ->where('user_id', $user->id)
                        ->where('year', $year)
                        ->where('month', $month)
                        ->orderByDesc('views')
                        ->get()
                        ->map(fn (PostMonthlyStat $row) => [
                            'post_id' => $row->post_id,
                            'views' => $row->views,
                            'likes' => $row->likes,
                            'comments' => $row->comments,
                            'saves' => $row->saves,
                        ]),
                ];
            } else {
                $metrics = AuthorStatsSnapshot::computeMonthMetrics($user, $year, $month);
                AuthorStatsSnapshot::snapshotMonth($user, $year, $month);
            }
        }

        $postIds = $metrics['posts']->pluck('post_id');
        $postsById = Post::query()
            ->whereIn('id', $postIds)
            ->with('authorAlias')
            ->get()
            ->keyBy('id');

        $posts = $metrics['posts']
            ->map(function (array $row) use ($postsById) {
                $post = $postsById->get($row['post_id']);
                if (! $post) {
                    return null;
                }

                return [
                    'post' => $post,
                    'views' => $row['views'],
                    'likes' => $row['likes'],
                    'comments' => $row['comments'],
                    'saves' => $row['saves'],
                ];
            })
            ->filter()
            ->values();

        return [
            'year' => $year,
            'month' => $month,
            'label' => $start->format('F Y'),
            'range_label' => $start->format('M j, Y').' – '.$end->format('M j, Y').' (UTC)',
            'views' => $metrics['views'],
            'likes' => $metrics['likes'],
            'comments' => $metrics['comments'],
            'saves' => $metrics['saves'],
            'followers_gained' => $metrics['followers_gained'],
            'subscribers_gained' => $metrics['subscribers_gained'],
            'posts' => $posts,
        ];
    }

    /**
     * @return Collection<int, array{year: int, month: int, label: string, value: string}>
     */
    public static function monthOptions(User $user): Collection
    {
        $snapshotMonths = AuthorMonthlyStat::query()
            ->where('user_id', $user->id)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get(['year', 'month']);

        $firstPostAt = Post::query()
            ->where('user_id', $user->id)
            ->whereNotNull('published_at')
            ->min('published_at');

        $start = $firstPostAt
            ? Carbon::parse($firstPostAt, 'UTC')->startOfMonth()
            : now('UTC')->startOfMonth();

        $cursor = now('UTC')->startOfMonth();
        $options = collect();

        while ($cursor->gte($start)) {
            $options->push([
                'year' => $cursor->year,
                'month' => $cursor->month,
                'label' => $cursor->format('F Y'),
                'value' => $cursor->year.'-'.$cursor->month,
            ]);
            $cursor->subMonth();
        }

        foreach ($snapshotMonths as $row) {
            $value = $row->year.'-'.$row->month;
            if ($options->contains(fn (array $option) => $option['value'] === $value)) {
                continue;
            }

            $date = Carbon::create($row->year, $row->month, 1, 0, 0, 0, 'UTC');
            $options->push([
                'year' => $row->year,
                'month' => $row->month,
                'label' => $date->format('F Y'),
                'value' => $value,
            ]);
        }

        return $options
            ->unique('value')
            ->sortByDesc(fn (array $option) => sprintf('%04d-%02d', $option['year'], $option['month']))
            ->values();
    }
}
