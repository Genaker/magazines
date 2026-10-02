<x-app-layout>
    <x-profile-layout>
        <h1 class="text-3xl font-bold mb-6">Request a category</h1>

        <form method="POST" action="{{ route('category-requests.store') }}" class="max-w-xl">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Category name</label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded-md border-gray-300" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Why is this category needed?</label>
                <textarea name="reason" rows="4" class="w-full rounded-md border-gray-300" required>{{ old('reason') }}</textarea>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-md">Submit request</button>
        </form>
    </x-profile-layout>
</x-app-layout>
