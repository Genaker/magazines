<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold">Edit story</h1>
            <span id="autosave-status" class="text-sm text-gray-500"></span>
        </div>

        @include('partials.share-link', ['post' => $post])

        @if ($hasPendingAutosave ?? false)
            <div class="mb-6 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ __('app.autosave_pending_published_draft') }}
            </div>
        @endif

        <form id="post-form"
              method="POST"
              action="{{ route('posts.update', $post) }}"
              enctype="multipart/form-data"
              data-autosave-url="{{ route('posts.autosave') }}"
              data-media-upload-url="{{ route('media.upload') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="post_id" value="{{ $post->id }}">

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Title</label>
                <input type="text" name="title" value="{{ old('title', $editorState['title'] ?? $post->title) }}" class="w-full rounded-md border border-gray-300 px-3 py-2" required>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Subtitle</label>
                <input type="text" name="subtitle" value="{{ old('subtitle', $editorState['subtitle'] ?? $post->subtitle) }}" class="w-full rounded-md border border-gray-300 px-3 py-2">
            </div>

        @include('partials.post-magazine-category-fields', [
            'magazines' => $magazines,
            'siteCategories' => $siteCategories,
            'magazineCategoryTrees' => $magazineCategoryTrees,
            'selectedMagazineId' => $selectedMagazineId ?? null,
            'selectedCategoryId' => $selectedCategoryId ?? null,
        ])

        @include('partials.post-magazine-status', ['post' => $post])

        @feature('ai_writing_tools')
                @include('partials.ai-writing-tools')
            @endfeature

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Body</label>
                <textarea id="editor-textarea" name="body" class="w-full rounded-md border border-gray-300">{{ old('body', $editorState['body'] ?? $post->body) }}</textarea>
            </div>

            @include('partials.tag-input', [
                'label' => 'Tags (max '.config('media.max_tags_per_post').')',
                'value' => old('tags', $editorState['tags'] ?? $post->tags->pluck('name')->join(', ')),
            ])

            @if ($magazines->isNotEmpty() && ! in_array($post->magazine_submission_status?->value, ['pending', 'approved'], true))
                <div class="mb-4 p-4 bg-gray-50 border border-gray-200 rounded-md">
                    <p class="text-sm font-medium mb-2">Submit to magazine for editorial review</p>
                    @foreach ($magazines as $magazine)
                        <button type="submit"
                                form="magazine-submit-{{ $magazine->id }}"
                                class="px-3 py-1 border rounded text-sm inline-block mr-2 mb-2">
                            Submit to {{ $magazine->name }}
                        </button>
                    @endforeach
                </div>
            @endif

            @include('partials.cover-upload', ['existingCoverUrl' => $coverUrl])

            @include('partials.share-image-upload', ['existingShareImageUrl' => $shareImageUrl ?? null])

            @include('partials.custom-fields-input', ['context' => \App\Support\CustomFieldSchema::CONTEXT_POST, 'value' => $post->custom_fields, 'heading' => 'Additional metadata'])

            @include('partials.author-alias-select', ['post' => $post, 'activeAlias' => null])

            <div class="mb-6">
                <label class="block text-sm font-medium mb-1">Status</label>
                <select name="status" class="w-full rounded-md border border-gray-300 px-3 py-2">
                    @foreach (['draft', 'published', 'unlisted'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $post->status->value) === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>

            @include('partials.publish-at', [
                'publishAt' => old('publish_at', $post->published_at?->format('Y-m-d\TH:i')),
            ])

            <div class="flex items-center gap-4">
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-md">Save</button>
                <button type="submit" form="delete-post-form" class="px-4 py-2 text-red-600 border border-red-300 rounded-md" onclick="return confirm('Delete this story?')">Delete</button>
            </div>
        </form>

        @foreach ($magazines as $magazine)
            <form id="magazine-submit-{{ $magazine->id }}"
                  method="POST"
                  action="{{ route('magazines.posts.submit', [$magazine, $post]) }}"
                  class="hidden">
                @csrf
            </form>
        @endforeach

        <form id="delete-post-form" method="POST" action="{{ route('posts.destroy', $post) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>

    @push('scripts')
        @vite('resources/js/editor.js')
    @endpush
</x-app-layout>
