<?php

namespace App\Http\Controllers;

use App\Support\SiteBranding;
use Illuminate\Http\JsonResponse;

class WebManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $name = SiteBranding::get('name');
        $description = SiteBranding::get('tagline');

        return response()->json([
            'name' => $name,
            'short_name' => $name,
            'description' => $description,
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'background_color' => SiteBranding::get('background_color'),
            'theme_color' => SiteBranding::get('theme_color'),
            'icons' => [
                [
                    'src' => asset('images/pwa/icon-192.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('images/pwa/icon-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('images/pwa/icon-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json',
        ]);
    }
}
