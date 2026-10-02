<x-admin-layout>
    <div class="max-w-xl mx-auto px-4 py-8">
        <p class="text-sm text-gray-500 mb-2"><a href="{{ route('admin.tags.index') }}" class="hover:text-gray-800">Tags</a></p>
        <h1 class="text-3xl font-bold mb-2">{{ $tag->display_name }}</h1>
        <p class="text-gray-600 mb-6">Assign moderators who can fix tags and hide mis-tagged posts from feeds.</p>

        <form method="POST" action="{{ route('admin.tags.update', $tag) }}">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Moderator emails</label>
                <p class="text-sm text-gray-500 mb-2">One email per line.</p>
                <textarea name="moderator_emails" rows="6" class="w-full rounded-md border-gray-300 font-mono text-sm">{{ old('moderator_emails', $moderatorEmails) }}</textarea>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-md">Save moderators</button>
        </form>
    </div>
</x-admin-layout>
