<?php

use App\Support\StaticAssetVersion;

if (! function_exists('static_asset')) {
    /** Return an asset URL with the current static-asset cache-bust version. */
    function static_asset(string $path): string
    {
        return StaticAssetVersion::append(asset($path));
    }
}
