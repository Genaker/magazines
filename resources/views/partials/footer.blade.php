@php
    use App\Support\SiteBranding;

    $siteName = SiteBranding::get('name');
    $year = now()->year;
    $startYear = SiteBranding::get('copyright_start_year') ?: '2014';
@endphp

<footer class="w-full border-t border-gray-100 bg-white mt-auto">
    <div class="w-full px-4 py-6 text-center text-sm text-gray-500">
        <p class="font-medium text-gray-700">{{ SiteBranding::get('footer_tagline') }}</p>
        <p class="mt-1">
            &copy; {{ $startYear }}&ndash;{{ $year }} {{ $siteName }}. {{ SiteBranding::get('footer_rights') }}
        </p>
    </div>
</footer>
