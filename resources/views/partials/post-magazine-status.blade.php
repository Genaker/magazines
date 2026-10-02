@if ($post->magazine)
    @php
        $magazineUrl = \App\Support\MagazineSubdomain::canonicalMagazineUrl($post->magazine);
        $postUrl = \App\Support\PostUrl::for($post);
        $status = $post->magazine_submission_status?->value;
    @endphp
    <div class="mb-4 rounded-md border px-4 py-3 text-sm @if ($status === 'approved') border-green-200 bg-green-50 text-green-900 @elseif ($status === 'pending') border-amber-200 bg-amber-50 text-amber-900 @else border-gray-200 bg-gray-50 text-gray-800 @endif">
        @if ($status === 'approved')
            <p class="font-medium">{{ __('message.post_published_in_magazine', ['magazine' => $post->magazine->name]) }}</p>
            <p class="mt-1">
                <a href="{{ $magazineUrl }}" class="underline">{{ $post->magazine->name }}</a>
                ·
                <a href="{{ $postUrl }}" class="underline">{{ __('app.view_on_magazine_site') }}</a>
            </p>
        @elseif ($status === 'pending')
            <p class="font-medium">{{ __('message.post_pending_magazine', ['magazine' => $post->magazine->name]) }}</p>
        @else
            <p class="font-medium">{{ __('app.post_assigned_to_magazine', ['magazine' => $post->magazine->name]) }}</p>
        @endif
    </div>
@endif
