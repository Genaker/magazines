<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Install — Magazines</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @if (file_exists(public_path('build/manifest.json')))
            @vite(['resources/css/app.css'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
        @endif
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-100 min-h-screen">
        <div class="max-w-3xl mx-auto px-4 py-10">
            <div class="text-center mb-8">
                <x-application-logo class="h-16 w-auto mx-auto" />
                <h1 class="mt-4 text-2xl font-semibold">Install Magazines</h1>
                <p class="mt-2 text-sm text-gray-600">Set up your publishing platform in a few steps.</p>
            </div>

            <div class="bg-white shadow-md rounded-lg px-6 py-6">
                @yield('content')
            </div>
        </div>
    </body>
</html>
