@props([
    'class' => 'h-10 w-auto',
])

<img
    src="{{ asset('images/magazine-logo.png') }}"
    alt="{{ \App\Models\SiteSetting::getValue('site_name', config('app.name')) }}"
    {{ $attributes->except('class') }}
    class="{{ $class }}"
/>
