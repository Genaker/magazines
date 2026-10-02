<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Admin — {{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="{{ asset('images/favicon.png') }}" type="image/png">
        @include('partials.pwa')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @if (file_exists(public_path('build/manifest.json')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
            <script src="{{ static_asset('js/app.js') }}"></script>
        @endif
    </head>
    <body class="font-sans antialiased bg-gray-100">
        <div class="min-h-screen flex flex-col">
            @include('partials.admin-path-warning')
            @include('partials.messages')

            @include('layouts.admin-topbar')

            <x-hook name="admin.content.before" :data="get_defined_vars()" />
            <main class="flex-1">
                <div class="max-w-7xl mx-auto px-4 py-8">
                    <div class="flex flex-col lg:flex-row gap-8 items-start">
                        @include('layouts.admin-navigation')

                        <div class="flex-1 min-w-0">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </main>

            @include('partials.footer')
        </div>

        @stack('scripts')
    </body>
</html>
