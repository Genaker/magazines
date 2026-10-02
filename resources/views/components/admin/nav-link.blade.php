@props(['href', 'active' => false])

<a
    href="{{ $href }}"
    {{ $attributes->class([
        'block rounded-md px-3 py-2 text-sm font-medium',
        'bg-gray-800 text-white' => $active,
        'text-gray-300 hover:bg-gray-800 hover:text-white' => ! $active,
    ]) }}
>
    {{ $slot }}
</a>
