<x-admin-layout>
    <div class="max-w-2xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-2">Custom fields</h1>
        <p class="text-sm text-gray-600 mb-6">Define extra fields for profiles, posts, and magazines. Users see normal inputs — not JSON.</p>

        @if (session('status') === 'custom-fields-updated')
            <p class="mb-4 text-sm text-green-700">Custom fields saved.</p>
        @endif

        <form method="POST" action="{{ route('admin.custom-fields.update') }}">
            @csrf
            @method('PUT')

            @include('admin.custom-fields.partials.field-definitions', [
                'context' => \App\Support\CustomFieldSchema::CONTEXT_USER,
                'title' => 'User profiles',
                'description' => 'Shown on the profile settings page and public author profile.',
                'definitions' => $definitions['user'] ?? [],
            ])

            @include('admin.custom-fields.partials.field-definitions', [
                'context' => \App\Support\CustomFieldSchema::CONTEXT_POST,
                'title' => 'Posts',
                'description' => 'Shown when writing or editing stories.',
                'definitions' => $definitions['post'] ?? [],
            ])

            @include('admin.custom-fields.partials.field-definitions', [
                'context' => \App\Support\CustomFieldSchema::CONTEXT_MAGAZINE,
                'title' => 'Magazines',
                'description' => 'Shown when creating a magazine.',
                'definitions' => $definitions['magazine'] ?? [],
            ])

            <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-md">Save custom fields</button>
        </form>
    </div>
</x-admin-layout>
