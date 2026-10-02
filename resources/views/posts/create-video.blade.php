<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 py-8">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <h1 class="text-3xl font-bold">{{ __('app.create_video') }}</h1>
            <div class="flex items-center gap-4 text-sm">
                <a href="{{ route('posts.create') }}" class="text-gray-600 hover:text-gray-900">{{ __('app.write_article_instead') }}</a>
                <a href="{{ route('posts.create.gallery') }}" class="text-gray-600 hover:text-gray-900">{{ __('app.create_gallery_instead') }}</a>
            </div>
        </div>

        <form id="video-form"
              method="POST"
              action="{{ route('posts.store') }}"
              data-media-upload-url="{{ route('media.upload') }}">
            @csrf
            <input type="hidden" name="type" value="video">

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">{{ __('app.title') }}</label>
                <input type="text" name="title" value="{{ old('title') }}" class="w-full rounded-md border border-gray-300 px-3 py-2" required>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">{{ __('app.subtitle') }}</label>
                <input type="text" name="subtitle" value="{{ old('subtitle') }}" class="w-full rounded-md border border-gray-300 px-3 py-2">
            </div>

            @include('partials.post-magazine-category-fields', [
                'magazines' => $magazines,
                'siteCategories' => $siteCategories,
                'magazineCategoryTrees' => $magazineCategoryTrees,
                'selectedMagazineId' => $selectedMagazineId ?? null,
                'selectedCategoryId' => $selectedCategoryId ?? null,
            ])

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1" for="video_url">{{ __('app.video_url') }}</label>
                <p class="text-sm text-gray-500 mb-2">{{ __('app.video_url_help') }}</p>
                <input type="url"
                       id="video_url"
                       name="video_url"
                       value="{{ old('video_url') }}"
                       placeholder="https://www.youtube.com/watch?v=..."
                       class="w-full rounded-md border border-gray-300 px-3 py-2">
                @error('video_url')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">{{ __('app.body') }}</label>
                <p class="text-sm text-gray-500 mb-2">{{ __('app.video_body_help') }}</p>
                <textarea id="editor-textarea" name="body" class="w-full rounded-md border border-gray-300">{{ old('body') }}</textarea>
            </div>

            @include('partials.tag-input', [
                'label' => __('app.tags_comma'),
                'value' => old('tags'),
                'placeholder' => 'documentary, interview',
            ])

            @include('partials.custom-fields-input', ['context' => \App\Support\CustomFieldSchema::CONTEXT_POST, 'heading' => 'Additional metadata'])

            @include('partials.author-alias-select', ['selectedAliasId' => $activeAlias->id])

            <div class="mb-6">
                <label class="block text-sm font-medium mb-1">{{ __('app.status') }}</label>
                <select name="status" class="w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="draft">{{ __('app.draft') }}</option>
                    <option value="published" @selected(old('status', 'published') === 'published')>{{ __('app.published') }}</option>
                    <option value="unlisted">{{ __('app.unlisted') }}</option>
                </select>
            </div>

            @include('partials.publish-at')

            <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-md">{{ __('app.publish_video') }}</button>
        </form>
    </div>

    @push('scripts')
        @vite('resources/js/editor.js')
    @endpush
</x-app-layout>
