<x-app-layout>
    <div class="max-w-xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Create a magazine</h1>

        <form method="POST" action="{{ route('magazines.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded-md border-gray-300" required>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Description</label>
                <textarea name="description" rows="4" class="w-full rounded-md border-gray-300">{{ old('description') }}</textarea>
            </div>

            <div class="mb-6">
                <label for="magazine-logo" class="block text-sm font-medium mb-1">Logo</label>
                <p class="text-xs text-gray-500 mb-2">Optional square image, JPG or PNG, max 5 MB.</p>
                <input type="file"
                       id="magazine-logo"
                       name="logo"
                       accept="image/jpeg,image/png,image/webp"
                       class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200">
            </div>

            @include('partials.custom-fields-input', ['context' => \App\Support\CustomFieldSchema::CONTEXT_MAGAZINE, 'heading' => 'Additional information'])

            <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-md">Create magazine</button>
        </form>
    </div>
</x-app-layout>
