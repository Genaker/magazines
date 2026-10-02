@php
    $shareUrl = \App\Support\PostUrl::for($post);
@endphp

@if (in_array($post->status->value, ['draft', 'unlisted']))
    <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-md text-sm">
        <p class="font-medium text-amber-900 mb-1">Share link ({{ $post->status->value }})</p>
        <div class="flex gap-2">
            <input type="text" readonly value="{{ $shareUrl }}" id="share-url" class="flex-1 rounded border-gray-300 text-xs">
            <button type="button" id="copy-share-url" class="px-3 py-1 bg-gray-900 text-white rounded text-xs">Copy</button>
        </div>
    </div>
@endif
