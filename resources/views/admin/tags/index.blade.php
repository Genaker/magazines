<x-admin-layout>
    <div class="max-w-4xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Tags</h1>

        @if (session('status'))
            <p class="mb-4 text-sm text-green-700">{{ session('status') }}</p>
        @endif

        <table class="w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="px-4 py-2">Tag</th>
                    <th class="px-4 py-2">Posts</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($tags as $tag)
                    <tr>
                        <td class="px-4 py-2">{{ $tag->display_name }}</td>
                        <td class="px-4 py-2">{{ $tag->published_posts_count }}</td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('admin.tags.edit', $tag) }}" class="text-indigo-600 hover:underline">Moderators</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-6">{{ $tags->links() }}</div>
    </div>
</x-admin-layout>
