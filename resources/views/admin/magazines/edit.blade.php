<x-admin-layout>
    <div class="max-w-xl mx-auto px-4 py-8">
        <p class="mb-4 text-sm">
            <a href="{{ route('admin.magazines.manage') }}" class="text-indigo-600 hover:underline">&larr; {{ __('app.admin_magazines') }}</a>
        </p>

        <h1 class="text-3xl font-bold mb-6">{{ __('app.admin_magazine_edit') }}</h1>

        <form method="POST" action="{{ route('admin.magazines.update', $magazine) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name', $magazine->name) }}" class="w-full rounded-md border-gray-300" required>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Slug</label>
                <p class="text-sm text-gray-600">{{ $magazine->slug }}</p>
                <p class="text-xs text-gray-500 mt-1">Updated automatically when the name changes.</p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Description</label>
                <textarea name="description" rows="4" class="w-full rounded-md border-gray-300">{{ old('description', $magazine->description) }}</textarea>
            </div>

            <div class="mb-6 rounded-lg border border-gray-200 p-4">
                <input type="hidden" name="require_post_approval" value="0">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox"
                           name="require_post_approval"
                           value="1"
                           class="mt-1 rounded border-gray-300"
                           @checked(old('require_post_approval', $magazine->require_post_approval))>
                    <span>
                        <span class="block text-sm font-medium">{{ __('app.magazine_require_post_approval') }}</span>
                        <span class="block text-xs text-gray-500 mt-1">{{ __('app.magazine_require_post_approval_help') }}</span>
                    </span>
                </label>
            </div>

            <div class="mb-6">
                <label for="magazine-logo" class="block text-sm font-medium mb-1">Logo</label>
                @if ($magazine->coverImageUrl())
                    <img src="{{ $magazine->coverImageUrl() }}" alt="" class="mb-2 h-16 w-16 rounded object-cover">
                @endif
                <p class="text-xs text-gray-500 mb-2">Optional square image, JPG or PNG, max 5 MB.</p>
                <input type="file"
                       id="magazine-logo"
                       name="logo"
                       accept="image/jpeg,image/png,image/webp"
                       class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200">
            </div>

            @include('partials.custom-fields-input', [
                'context' => \App\Support\CustomFieldSchema::CONTEXT_MAGAZINE,
                'heading' => 'Additional information',
                'value' => $magazine->custom_fields ?? [],
            ])

            <div class="flex gap-3">
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-md">Save</button>
                <a href="{{ route('magazines.show', $magazine) }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm" target="_blank" rel="noopener">View public page</a>
            </div>
        </form>
    </div>
</x-admin-layout>
