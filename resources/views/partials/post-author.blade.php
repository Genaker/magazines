@php
    $author = $post->authorAlias;
@endphp

@if ($author && ! $author->isRetired())
    <a href="{{ \App\Support\SiteUrl::author($author) }}" @class(['font-medium', $class ?? ''])>{{ $author->name }}</a>
@else
    <span @class(['text-gray-500', $class ?? ''])>Unavailable author</span>
@endif
