<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col lg:flex-row gap-8 items-start">
        @include('profile.partials.sidebar')
        <x-hook name="profile.sidebar.after" />

        <div class="flex-1 min-w-0">
            {{ $slot }}
        </div>
    </div>
</div>
