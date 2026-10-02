<?php

namespace App\Http\Controllers;

use App\Enums\AuthorSubscriptionDelivery;
use App\Events\AuthorSubscribed;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuthorSubscriptionController extends Controller
{
    public function toggle(Request $request, User $user): JsonResponse
    {
        abort_if($request->user()->id === $user->id, 422, 'Cannot subscribe to yourself.');

        $subscription = $request->user()
            ->storySubscriptions()
            ->where('author_id', $user->id)
            ->first();

        if ($subscription) {
            if ($request->filled('delivery')) {
                $request->validate([
                    'delivery' => ['required', Rule::enum(AuthorSubscriptionDelivery::class)],
                ]);

                $request->user()->storySubscriptions()->updateExistingPivot($user->id, [
                    'email_delivery' => $request->input('delivery'),
                ]);

                return response()->json([
                    'subscribed' => true,
                    'delivery' => $request->input('delivery'),
                ]);
            }

            $request->user()->storySubscriptions()->detach($user->id);

            return response()->json(['subscribed' => false]);
        }

        $default = config('subscriptions.default_delivery', AuthorSubscriptionDelivery::Instant->value);
        $delivery = AuthorSubscriptionDelivery::tryFrom($request->input('delivery', $default))
            ?? AuthorSubscriptionDelivery::Instant;

        $request->user()->storySubscriptions()->attach($user->id, [
            'email_delivery' => $delivery->value,
        ]);

        AuthorSubscribed::dispatch($user, $request->user());

        return response()->json([
            'subscribed' => true,
            'delivery' => $delivery->value,
        ]);
    }

    public function unsubscribe(Request $request, User $subscriber, User $author): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        if ($request->user() && $request->user()->id !== $subscriber->id) {
            abort(403);
        }

        $subscriber->storySubscriptions()->detach($author->id);

        return redirect()
            ->route('authors.show', $author)
            ->with('status', 'You have been unsubscribed from email updates.');
    }
}
