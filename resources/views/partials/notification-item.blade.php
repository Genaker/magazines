@php
    $data = $notification->data;
    $isUnread = $notification->read_at === null;
    $actorUrl = isset($data['actor_username']) ? route('authors.show', $data['actor_username']) : '#';
    $postUrl = isset($data['post_author_username'], $data['post_slug'])
        ? route('posts.show', [$data['post_author_username'], $data['post_slug']])
            .(isset($data['comment_id']) ? '#comment-'.$data['comment_id'] : '')
        : null;
    $magazineUrl = isset($data['magazine_slug']) ? route('magazines.show', $data['magazine_slug']) : null;
@endphp

<form method="POST" action="{{ route('notifications.read-one', $notification->id) }}" class="block">
    @csrf
    <button
        type="submit"
        @class([
            'w-full text-left flex gap-4 py-5 px-2 -mx-2 rounded-lg transition hover:bg-gray-50',
            'bg-green-50/60' => $isUnread,
        ])
    >
        <span class="shrink-0">
            <x-user-avatar
                :username="$data['actor_username'] ?? ''"
                :avatar="$data['actor_avatar'] ?? null"
                size="md"
                :alt="$data['actor_name'] ?? 'Someone'"
            />
        </span>

        <span class="min-w-0 flex-1">
            <span class="block font-semibold text-gray-900">{{ $data['actor_name'] ?? 'Someone' }}</span>
            <span class="block text-sm text-gray-800 mt-0.5">
                @switch($data['kind'] ?? '')
                    @case('follow')
                        followed you
                        @break
                    @case('story_subscription')
                        subscribed to get email notifications for your stories
                        @break
                    @case('like')
                        clapped for
                        @if ($postUrl)
                            <span class="font-medium">{{ $data['post_title'] }}</span>
                        @endif
                        @break
                    @case('bookmark')
                        saved your story
                        @if ($postUrl)
                            <span class="font-medium">{{ $data['post_title'] }}</span>
                        @endif
                        to their reading list
                        @break
                    @case('comment')
                        commented on
                        @if ($postUrl)
                            <span class="font-medium">{{ $data['post_title'] }}</span>
                        @endif
                        @break
                    @case('comment_reply')
                        replied to your comment on
                        @if ($postUrl)
                            <span class="font-medium">{{ $data['post_title'] }}</span>
                        @endif
                        @break
                    @case('comment_like')
                        liked your comment on
                        @if ($postUrl)
                            <span class="font-medium">{{ $data['post_title'] }}</span>
                        @endif
                        @break
                    @case('comment_mention')
                        mentioned you in a comment on
                        @if ($postUrl)
                            <span class="font-medium">{{ $data['post_title'] }}</span>
                        @endif
                        @break
                    @case('magazine_join_request')
                        requested to join
                        @if ($magazineUrl)
                            <span class="font-medium">{{ $data['magazine_name'] }}</span>
                        @endif
                        @break
                    @case('magazine_join_approved')
                        approved your request to join
                        @if ($magazineUrl)
                            <span class="font-medium">{{ $data['magazine_name'] }}</span>
                        @endif
                        @break
                    @default
                        interacted with your content
                @endswitch
            </span>
            <time datetime="{{ $notification->created_at->toIso8601String() }}" class="block text-sm text-gray-500 mt-1">
                {{ $notification->created_at->format('M j, Y') }}
            </time>
        </span>
    </button>
</form>
