<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @include('partials.meta-tags', ['seo' => $seo ?? null])

        @include('partials.site-fonts')

        @if (file_exists(public_path('build/manifest.json')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
            <script src="{{ static_asset('js/app.js') }}"></script>
        @endif
    </head>
    <body class="font-sans text-ink antialiased">
        <div class="min-h-screen flex flex-col items-stretch pt-6 sm:pt-0 bg-canvas">
            <div class="flex-1 flex flex-col sm:justify-center items-center w-full">
                <div>
                    <a href="/">
                        <x-application-logo class="h-20 w-auto" />
                    </a>
                </div>

                <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white border border-gray-100 overflow-hidden sm:rounded-lg">
                    {{ $slot }}
                </div>
            </div>

            @include('partials.footer')
        </div>
    </body>
</html>
