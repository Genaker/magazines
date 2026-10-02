<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold">Write a story</h1>
            <div class="flex items-center gap-4">
                <a href="{{ route('posts.create.gallery') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('app.create_gallery_instead') }}</a>
                <a href="{{ route('posts.create.video') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('app.create_video_instead') }}</a>
                <span id="autosave-status" class="text-sm text-gray-500"></span>
            </div>
        </div>

        <form id="post-form"
              method="POST"
              action="{{ route('posts.store') }}"
              enctype="multipart/form-data"
              data-autosave-url="{{ route('posts.autosave') }}"
              data-media-upload-url="{{ route('media.upload') }}"
              data-update-url="{{ route('posts.update', ['post' => '__POST__']) }}">
            @csrf
            <input type="hidden" name="post_id" value="">

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Title</label>
                <input type="text" name="title" value="{{ old('title') }}" class="w-full rounded-md border border-gray-300 px-3 py-2" required>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Subtitle</label>
                <input type="text" name="subtitle" value="{{ old('subtitle') }}" class="w-full rounded-md border border-gray-300 px-3 py-2">
            </div>

            @include('partials.post-magazine-category-fields', [
                'magazines' => $magazines,
                'siteCategories' => $siteCategories,
                'magazineCategoryTrees' => $magazineCategoryTrees,
                'selectedMagazineId' => $selectedMagazineId ?? null,
                'selectedCategoryId' => $selectedCategoryId ?? null,
            ])

            @feature('ai_writing_tools')
                @include('partials.ai-writing-tools')
            @endfeature

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Body</label>
                <textarea id="editor-textarea" name="body" class="w-full rounded-md border border-gray-300">{{ old('body') }}</textarea>
            </div>

            @include('partials.tag-input', [
                'label' => 'Tags (comma separated, max '.config('media.max_tags_per_post').')',
                'value' => old('tags'),
                'placeholder' => 'technology, startups, writing',
            ])

            @include('partials.cover-upload')

            @include('partials.share-image-upload')

            @include('partials.custom-fields-input', ['context' => \App\Support\CustomFieldSchema::CONTEXT_POST, 'heading' => 'Additional metadata'])

            @include('partials.author-alias-select', ['selectedAliasId' => $activeAlias->id])

            <div class="mb-6">
                <label class="block text-sm font-medium mb-1">Status</label>
                <select name="status" class="w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="draft">Draft</option>
                    <option value="published" selected>Published</option>
                    <option value="unlisted">Unlisted</option>
                </select>
            </div>

            @include('partials.publish-at')

            <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-md">Publish</button>
        </form>
    </div>

    @push('scripts')
        @vite('resources/js/editor.js')
    @endpush
</x-app-layout>
