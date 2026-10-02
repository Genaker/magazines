<?php

namespace App\Http\Controllers;

use App\Models\AuthorAlias;
use App\Support\AvatarImageGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AvatarController extends Controller
{
    public function author(AuthorAlias $alias): Response
    {
        return $this->download($alias->username, $alias->avatar);
    }

    public function profile(Request $request): Response
    {
        $user = $request->user();

        return $this->download($user->username, $user->avatar);
    }

    private function download(string $username, ?string $avatarPath): Response
    {
        $filename = $username.'-avatar.jpg';

        if ($avatarPath !== null && Storage::disk('public')->exists($avatarPath)) {
            return response()->download(
                Storage::disk('public')->path($avatarPath),
                $filename,
            );
        }

        if (! extension_loaded('gd')) {
            abort(503, 'Avatar image generation is unavailable.');
        }

        $binary = app(AvatarImageGenerator::class)->render($username);

        return response($binary, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
