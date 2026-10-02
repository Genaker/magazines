@props(['column', 'label', 'sort', 'dir'])

@php
    $active = $sort === $column;
    $nextDir = $active && $dir === 'asc' ? 'desc' : 'asc';
    $arrow = $active ? ($dir === 'asc' ? '↑' : '↓') : '';
@endphp

<a
    href="{{ request()->fullUrlWithQuery(['sort' => $column, 'dir' => $nextDir, 'page' => 1]) }}"
    class="inline-flex items-center gap-1 font-medium text-gray-700 hover:text-gray-900 {{ $active ? 'text-indigo-700' : '' }}"
>
    {{ $label }}
    @if ($arrow)
        <span class="text-xs" aria-hidden="true">{{ $arrow }}</span>
    @endif
</a>
