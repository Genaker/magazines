<?php

namespace App\Http\Controllers;

use App\Events\UserFollowed;
use App\Models\Category;
use App\Models\Magazine;
use App\Models\Tag;
use App\Models\User;
use App\Support\EntityCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function toggleUser(Request $request, User $user): JsonResponse
    {
        $following = $request->user()->following()->where('following_id', $user->id)->exists();

        if ($following) {
            $request->user()->following()->detach($user->id);
            $this->flushFollowCaches($request->user()->id, $user->id);

            return response()->json(['following' => false]);
        }

        $request->user()->following()->attach($user->id);
        $this->flushFollowCaches($request->user()->id, $user->id);
        UserFollowed::dispatch($user, $request->user());

        return response()->json(['following' => true]);
    }

    public function toggleCategory(Request $request, Category $category): JsonResponse
    {
        $following = $request->user()->followedCategories()->where('category_id', $category->id)->exists();

        if ($following) {
            $request->user()->followedCategories()->detach($category->id);
            $this->flushFeedCacheFor($request->user()->id);

            return response()->json(['following' => false]);
        }

        $request->user()->followedCategories()->attach($category->id);
        $this->flushFeedCacheFor($request->user()->id);

        return response()->json(['following' => true]);
    }

    public function toggleMagazine(Request $request, Magazine $magazine): JsonResponse
    {
        $following = $request->user()->followedMagazines()->where('magazine_id', $magazine->id)->exists();

        if ($following) {
            $request->user()->followedMagazines()->detach($magazine->id);

            return response()->json(['following' => false]);
        }

        $request->user()->followedMagazines()->attach($magazine->id);

        return response()->json(['following' => true]);
    }

    public function toggleTag(Request $request, Tag $tag): JsonResponse
    {
        $following = $request->user()->followedTags()->where('tag_id', $tag->id)->exists();

        if ($following) {
            $request->user()->followedTags()->detach($tag->id);
            $this->flushFeedCacheFor($request->user()->id);

            return response()->json(['following' => false]);
        }

        $request->user()->followedTags()->attach($tag->id);
        $this->flushFeedCacheFor($request->user()->id);

        return response()->json(['following' => true]);
    }

    private function flushFollowCaches(int $followerId, int $followedId): void
    {
        $this->flushFeedCacheFor($followerId);
        EntityCache::forget(EntityCache::key('authors', 'stats', $followedId));
        EntityCache::flushTag(config('entity-cache.tags.authors'));
    }

    private function flushFeedCacheFor(int $userId): void
    {
        EntityCache::flushTag(config('entity-cache.tags.feeds'));
    }
}
