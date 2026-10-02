@props([
    'user' => null,
    'author' => null,
    'username' => null,
    'avatar' => null,
    'size' => 'md',
    'href' => null,
    'ring' => false,
    'alt' => '',
])

@php
    $subject = $author ?? $user;
    $uname = $username ?? $subject?->username ?? '';
    $path = $avatar ?? $subject?->avatar;
    $initials = \App\Support\AvatarInitials::fromUsername($uname);
    $sizes = [
        'sm' => ['class' => 'h-9 w-9 text-xs', 'px' => 36],
        'md' => ['class' => 'h-12 w-12 text-sm', 'px' => 48],
        'lg' => ['class' => 'h-16 w-16 text-base', 'px' => 64],
        'xl' => ['class' => 'h-20 w-20 text-lg', 'px' => 80],
    ];
    $sizeConfig = $sizes[$size] ?? $sizes['md'];
    $ringClass = $ring ? ' ring-1 ring-gray-200' : '';
    $imgClass = $sizeConfig['class'].' rounded-full object-cover shrink-0'.$ringClass;
    $initialsClass = 'flex '.$sizeConfig['class'].' items-center justify-center rounded-full shrink-0 font-semibold'.$ringClass;
    $wrapperClass = trim(($href ? 'shrink-0 ' : 'inline-flex shrink-0 ').($attributes->get('class') ?? ''));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->except('class')->merge(['class' => $wrapperClass]) }}>
@else
    <span {{ $attributes->except('class')->merge(['class' => $wrapperClass]) }}>
@endif
    @if ($path)
        <img
            src="{{ asset('storage/'.$path) }}"
            alt="{{ $alt }}"
            class="{{ $imgClass }}"
            width="{{ $sizeConfig['px'] }}"
            height="{{ $sizeConfig['px'] }}"
        >
    @else
        <span
            class="{{ $initialsClass }}"
            style="{{ \App\Support\AvatarInitials::backgroundStyle($uname) }}; color: {{ \App\Support\AvatarInitials::textColor($uname) }}"
            @if ($alt === '') aria-hidden="true" @else aria-label="{{ $alt }}" @endif
        >{{ $initials }}</span>
    @endif
@if ($href)
    </a>
@else
    </span>
@endif
