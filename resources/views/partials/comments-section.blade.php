<section id="comments" class="mt-10 pt-8 border-t border-gray-100 pb-4">
    @php
        $commentSort = $commentSort ?? 'new';
        $postShowUrl = \App\Support\PostUrl::for($post);
    @endphp

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <h2 class="text-2xl font-bold">Discussion ({{ $commentCount ?? $comments->count() }})</h2>
        <nav class="flex gap-1 rounded-lg border border-gray-200 p-1 text-sm" aria-label="Comment sort">
            <a
                href="{{ $postShowUrl }}#comments"
                @class([
                    'rounded-md px-3 py-1.5 font-medium transition',
                    'bg-gray-900 text-white' => $commentSort === 'new',
                    'text-gray-600 hover:text-gray-900' => $commentSort !== 'new',
                ])
            >
                New
            </a>
            <a
                href="{{ $postShowUrl }}?comments=top#comments"
                @class([
                    'rounded-md px-3 py-1.5 font-medium transition',
                    'bg-gray-900 text-white' => $commentSort === 'top',
                    'text-gray-600 hover:text-gray-900' => $commentSort !== 'top',
                ])
            >
                Top
            </a>
        </nav>
    </div>

    @if (session('status') === 'comment-posted')
        <p class="mb-4 text-sm text-green-700">Comment posted.</p>
    @endif

    @if ($canComment)
        <form method="POST" action="{{ route('posts.comments.store', $post) }}" class="mb-8 p-4 bg-gray-50 rounded-lg">
            @csrf
            <label for="comment-body" class="block text-sm font-medium text-gray-700 mb-2">Join the discussion</label>
            <textarea id="comment-body" name="body" rows="4" required maxlength="5000" placeholder="Share your thoughts… Use @username to mention someone." class="w-full rounded-md border-gray-300">{{ old('body') }}</textarea>
            @error('body')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <button type="submit" class="mt-3 px-4 py-2 bg-gray-900 text-white text-sm rounded-md">Post comment</button>
        </form>
    @elseif (auth()->check())
        <p class="mb-8 text-sm text-gray-500">You cannot comment on this post.</p>
    @else
        <p class="mb-8 text-sm text-gray-600">
            <a href="{{ route('login') }}" class="underline">Log in</a> to join the discussion.
        </p>
    @endif

    @forelse ($comments as $comment)
        @include('partials.comment', [
            'comment' => $comment,
            'post' => $post,
            'canComment' => $canComment,
            'likedCommentIds' => $likedCommentIds ?? [],
        ])
    @empty
        <p class="text-gray-500 text-sm">No comments yet. Be the first to reply.</p>
    @endforelse
</section>

@once
    @push('scripts')
        <script>
            (function () {
                function csrfToken() {
                    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
                }

                function bindCommentLikes() {
                    const root = document.getElementById('comments');
                    if (! root || root.dataset.likesBound === '1') {
                        return;
                    }

                    root.dataset.likesBound = '1';
                    root.addEventListener('click', async (event) => {
                        const btn = event.target.closest('.comment-like-btn');
                        if (! btn) {
                            return;
                        }

                        event.preventDefault();
                        const response = await fetch(`/comments/${btn.dataset.commentId}/like`, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken(),
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (! response.ok) {
                            return;
                        }

                        const data = await response.json();
                        btn.dataset.liked = data.liked ? '1' : '0';
                        const countEl = btn.querySelector('.comment-like-count');
                        if (countEl) {
                            countEl.textContent = data.likes_count;
                        }
                    });
                }

                bindCommentLikes();
            })();
        </script>
    @endpush
@endonce
