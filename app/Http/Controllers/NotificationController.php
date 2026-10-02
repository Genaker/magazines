<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'notifications-read');
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        /** @var DatabaseNotification|null $notification */
        $notification = $request->user()
            ->notifications()
            ->whereKey($id)
            ->firstOrFail();

        $notification->markAsRead();

        $data = $notification->data;
        $url = match ($data['kind'] ?? null) {
            'follow', 'story_subscription' => isset($data['actor_username'])
                ? route('authors.show', $data['actor_username'])
                : route('notifications.index'),
            'like', 'bookmark', 'comment', 'comment_reply' => isset($data['post_author_username'], $data['post_slug'])
                ? route('posts.show', [$data['post_author_username'], $data['post_slug']])
                    .(isset($data['comment_id']) ? '#comment-'.$data['comment_id'] : '')
                : route('notifications.index'),
            'magazine_join_request' => isset($data['magazine_slug'])
                ? route('magazines.submissions', $data['magazine_slug'])
                : route('notifications.index'),
            'magazine_join_approved' => isset($data['magazine_slug'])
                ? route('magazines.show', $data['magazine_slug'])
                : route('notifications.index'),
            default => route('notifications.index'),
        };

        return redirect($url);
    }
}
