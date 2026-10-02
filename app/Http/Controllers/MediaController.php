<?php

namespace App\Http\Controllers;

use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function upload(Request $request, ImageService $images): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'max:'.config('media.max_upload_kb')],
        ]);

        $processed = $images->processUpload($request->file('file'), 'editor');

        return response()->json([
            'location' => $images->url($processed['path']),
        ]);
    }
}
