@php
    use App\Support\SiteBranding;
@endphp

<link rel="manifest" href="{{ route('manifest') }}">
<meta name="theme-color" content="{{ SiteBranding::get('theme_color') }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{{ SiteBranding::get('name') }}">
<link rel="apple-touch-icon" href="{{ asset('images/pwa/apple-touch-icon.png') }}">
