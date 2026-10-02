<x-admin-layout>
    <div class="max-w-5xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-2">Admin dashboard</h1>
        @if (auth()->user()->isSuperAdmin())
            <p class="text-sm text-indigo-700 mb-6">
                Super admin:
                <a href="{{ route('admin.settings.edit') }}" class="underline font-medium">Settings &amp; feature toggles</a>
                ·
                <a href="{{ route('admin.trash.index') }}" class="underline">Trash</a>
            </p>
        @else
            <p class="text-sm text-gray-600 mb-6">Moderator tools. Site settings require a super admin account.</p>
        @endif

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-4 rounded shadow"><p class="text-sm text-gray-500">Users</p><p class="text-2xl font-bold">{{ $stats['users'] }}</p></div>
            <div class="bg-white p-4 rounded shadow"><p class="text-sm text-gray-500">Posts</p><p class="text-2xl font-bold">{{ $stats['posts'] }}</p></div>
            <div class="bg-white p-4 rounded shadow"><p class="text-sm text-gray-500">User reports</p><p class="text-2xl font-bold">{{ $stats['reports'] }}</p></div>
            <div class="bg-white p-4 rounded shadow"><p class="text-sm text-gray-500">Pending requests</p><p class="text-2xl font-bold">{{ $stats['pending_category_requests'] }}</p></div>
        </div>

        <div class="flex gap-4 text-sm mb-8">
            <a href="{{ route('admin.users.index') }}" class="underline">Users</a>
            <a href="{{ route('admin.posts.index') }}" class="underline">Posts</a>
            <a href="{{ route('admin.categories.index') }}" class="underline">Categories</a>
            @feature('magazines')
                <a href="{{ route('admin.magazines.manage') }}" class="underline">{{ __('app.admin_magazines') }}</a>
                <a href="{{ route('admin.magazines.index') }}" class="underline">{{ __('app.admin_magazines_nav') }}</a>
            @endfeature
            <a href="{{ route('admin.custom-fields.edit') }}" class="underline">Custom fields</a>
            @feature('category_requests')
                <a href="{{ route('admin.category-requests.index') }}" class="underline">Category requests</a>
            @endfeature
            @feature('user_reports')
                <a href="{{ route('admin.user-reports.index') }}" class="underline">User reports</a>
            @endfeature
            @if (auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.settings.edit') }}" class="underline">Settings</a>
                <a href="{{ route('admin.trash.index') }}" class="underline">Trash</a>
            @endif
        </div>

        <h2 class="text-xl font-semibold mb-4">Top posts by views</h2>
        @foreach ($topPosts as $post)
            <p class="mb-2">{{ $post->title }} — {{ $post->views_count }} views</p>
        @endforeach
    </div>
</x-admin-layout>
