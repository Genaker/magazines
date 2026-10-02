@php
    $seo = $seo ?? \App\Support\Seo::defaults();
@endphp

<link rel="icon" href="{{ asset('images/favicon.png') }}" type="image/png">
@include('partials.pwa')
<link rel="alternate" type="application/rss+xml" title="{{ \App\Support\Syndication::siteName() }} RSS" href="{{ route('syndication.rss') }}">
<link rel="alternate" type="application/atom+xml" title="{{ \App\Support\Syndication::siteName() }} Atom" href="{{ route('syndication.atom') }}">
<title>{{ $seo['title'] ?? config('app.name') }}</title>
<meta name="description" content="{{ $seo['description'] ?? '' }}">
<link rel="canonical" href="{{ $seo['url'] ?? url()->current() }}">

<meta property="og:type" content="{{ $seo['type'] ?? 'website' }}">
<meta property="og:title" content="{{ $seo['title'] ?? config('app.name') }}">
<meta property="og:description" content="{{ $seo['description'] ?? '' }}">
<meta property="og:url" content="{{ $seo['url'] ?? url()->current() }}">
<meta property="og:site_name" content="{{ $seo['site_name'] ?? config('app.name') }}">
@if (! empty($seo['image']))
    <meta property="og:image" content="{{ $seo['image'] }}">
    <meta property="og:image:secure_url" content="{{ $seo['image'] }}">
    @if (! empty($seo['image_alt']))
        <meta property="og:image:alt" content="{{ $seo['image_alt'] }}">
    @endif
    @if (! empty($seo['image_width']))
        <meta property="og:image:width" content="{{ $seo['image_width'] }}">
    @endif
    @if (! empty($seo['image_height']))
        <meta property="og:image:height" content="{{ $seo['image_height'] }}">
    @endif
@endif
@if (! empty($seo['author']))
    <meta property="article:author" content="{{ $seo['author'] }}">
@endif
@if (! empty($seo['section']))
    <meta property="article:section" content="{{ $seo['section'] }}">
@endif
@if (! empty($seo['published_time']))
    <meta property="article:published_time" content="{{ $seo['published_time'] }}">
@endif
@if (! empty($seo['modified_time']))
    <meta property="article:modified_time" content="{{ $seo['modified_time'] }}">
@endif

<meta name="twitter:card" content="{{ ! empty($seo['image']) ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seo['title'] ?? config('app.name') }}">
<meta name="twitter:description" content="{{ $seo['description'] ?? '' }}">
@if (! empty($seo['twitter_site']))
    <meta name="twitter:site" content="{{ $seo['twitter_site'] }}">
@endif
@if (! empty($seo['image']))
    <meta name="twitter:image" content="{{ $seo['image'] }}">
    @if (! empty($seo['image_alt']))
        <meta name="twitter:image:alt" content="{{ $seo['image_alt'] }}">
    @endif
@endif
