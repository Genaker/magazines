<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @include('partials.meta-tags', ['seo' => $seo ?? null])

        <x-hook name="head.after_meta" :data="get_defined_vars()" />

        @include('partials.site-fonts')

        @if (file_exists(public_path('build/manifest.json')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
            <script src="{{ static_asset('js/app.js') }}"></script>
        @endif
    </head>
    <body class="font-sans text-ink antialiased">
        <x-hook name="body.start" :data="get_defined_vars()" />
        <div class="min-h-screen bg-canvas flex flex-col">
            @include('layouts.navigation')
            <x-hook name="nav.after" :data="get_defined_vars()" />

            @include('partials.messages')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white border-b border-gray-100">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <x-hook name="main.before" :data="get_defined_vars()" />
            <main class="flex-1">
                {{ $slot }}
            </main>
            <x-hook name="main.after" :data="get_defined_vars()" />

            <x-hook name="footer.before" :data="get_defined_vars()" />
            @include('partials.footer')
        </div>

        @stack('scripts')
        <x-hook name="body.end" :data="get_defined_vars()" />
    </body>
</html>
