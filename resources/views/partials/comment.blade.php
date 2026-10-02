@php
    $depth = $depth ?? 0;
    $indent = min($depth, 6);
@endphp

<article
    id="comment-{{ $comment->id }}"
    @class([
        'group relative',
        'pt-4' => $depth === 0,
        'mt-3' => $depth > 0,
    ])
    @if ($indent > 0) style="margin-left: {{ $indent * 1.25 }}rem" @endif
>
    @if ($depth > 0)
        <span class="absolute -left-3 top-0 bottom-0 w-px bg-gray-200" aria-hidden="true"></span>
    @endif

    <div class="flex gap-3" x-data="{ replying: false, editing: false }">
        <a href="{{ route('authors.show', $comment->user) }}" class="shrink-0" title="View {{ $comment->user->name }}'s profile">
            <x-user-avatar :user="$comment->user" size="sm" ring :alt="$comment->user->name" />
        </a>

        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm leading-snug">
                        <a href="{{ route('authors.show', $comment->user) }}" class="font-semibold text-gray-900 hover:underline">
                            {{ $comment->user->name }}
                        </a>
                        <span class="text-gray-400">·</span>
                        <time datetime="{{ $comment->created_at->toIso8601String() }}" class="text-gray-500">
                            {{ $comment->created_at->diffForHumans() }}
                        </time>
                        @if ($comment->edited_at)
                            <span class="text-gray-400">· edited</span>
                        @endif
                    </p>
                    <div x-show="!editing" class="mt-1 text-gray-800 whitespace-pre-wrap break-words">{!! \App\Support\CommentFormatter::format($comment->body) !!}</div>
                    @can('update', $comment)
                        <form
                            x-show="editing"
                            x-cloak
                            method="POST"
                            action="{{ route('comments.update', $comment) }}"
                            class="mt-2"
                        >
                            @csrf
                            @method('PUT')
                            <textarea
                                name="body"
                                rows="3"
                                required
                                maxlength="5000"
                                class="w-full rounded-md border-gray-300 text-sm"
                            >{{ old('body', $comment->body) }}</textarea>
                            <div class="mt-2 flex gap-2">
                                <button type="submit" class="rounded-md bg-gray-900 px-3 py-1.5 text-sm text-white">Save</button>
                                <button type="button" @click="editing = false" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700">Cancel</button>
                            </div>
                        </form>
                    @endcan
                </div>

                <div class="flex shrink-0 items-start gap-2">
                @can('update', $comment)
                    <button
                        type="button"
                        @click="editing = !editing"
                        class="text-xs text-gray-600 opacity-0 transition group-hover:opacity-100 hover:underline"
                    >Edit</button>
                @endcan
                @can('delete', $comment)
                    <form method="POST" action="{{ route('comments.destroy', $comment) }}" onsubmit="return confirm('Delete this comment?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs text-red-600 opacity-0 transition group-hover:opacity-100 hover:underline">Delete</button>
                    </form>
                @endcan
                </div>
            </div>

            @if ($canComment || auth()->check())
                <div class="mt-2 flex flex-wrap items-center gap-3 text-sm">
                    @auth
                        <button
                            type="button"
                            class="comment-like-btn font-medium text-gray-600 hover:text-gray-900"
                            data-comment-id="{{ $comment->id }}"
                            data-liked="{{ in_array($comment->id, $likedCommentIds ?? []) ? '1' : '0' }}"
                        >
                            <span class="comment-like-count">{{ $comment->likes_count }}</span> likes
                        </button>
                    @elseif ($comment->likes_count > 0)
                        <span class="text-gray-500"><span>{{ $comment->likes_count }}</span> likes</span>
                    @endif

                    @if ($canComment)
                    <button
                        type="button"
                        @click="replying = !replying; if (replying) $nextTick(() => $refs.reply{{ $comment->id }}?.focus())"
                        class="font-medium text-gray-600 hover:text-gray-900"
                    >
                        Reply
                    </button>
                    @if (auth()->id() !== $comment->user->id)
                        <a href="{{ route('authors.show', $comment->user) }}" class="text-gray-500 hover:text-gray-800 hover:underline">
                            Profile
                        </a>
                    @endif
                    @endif
                </div>

                @if ($canComment)
                <form
                    x-show="replying"
                    x-cloak
                    method="POST"
                    action="{{ route('posts.comments.store', $post) }}"
                    class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-3"
                >
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                    <p class="mb-2 text-xs text-gray-500">
                        Replying to <span class="font-medium text-gray-700">{{ $comment->user->name }}</span>
                    </p>
                    <textarea
                        x-ref="reply{{ $comment->id }}"
                        name="body"
                        rows="3"
                        required
                        maxlength="5000"
                        class="w-full rounded-md border-gray-300 text-sm"
                        placeholder="Write a reply… Use @username to mention someone."
                    ></textarea>
                    <div class="mt-2 flex gap-2">
                        <button type="submit" class="rounded-md bg-gray-900 px-3 py-1.5 text-sm text-white">Post reply</button>
                        <button type="button" @click="replying = false" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700">Cancel</button>
                    </div>
                </form>
                @endif

            @endif

            @if ($comment->replies->isNotEmpty())
                <div class="mt-1">
                    @foreach ($comment->replies as $reply)
                        @include('partials.comment', [
                            'comment' => $reply,
                            'post' => $post,
                            'canComment' => $canComment,
                            'likedCommentIds' => $likedCommentIds ?? [],
                            'depth' => $depth + 1,
                        ])
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</article>
