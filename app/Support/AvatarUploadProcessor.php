<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/** Center-crops and resizes uploaded avatar images to a fixed square size. */
class AvatarUploadProcessor
{
    public function store(UploadedFile $file): string
    {
        if (! extension_loaded('gd')) {
            return $file->store('avatars', 'public');
        }

        $size = max(1, (int) config('media.avatar.size', 100));
        $jpegQuality = max(1, min(100, (int) config('media.avatar.jpeg_quality', 85)));
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) ? $extension : 'jpg';

        $source = $this->loadImage($file->getRealPath(), $extension);
        $width = imagesx($source);
        $height = imagesy($source);
        $cropSize = min($width, $height);
        $srcX = (int) (($width - $cropSize) / 2);
        $srcY = (int) (($height - $cropSize) / 2);

        $cropped = imagecreatetruecolor($cropSize, $cropSize);
        imagecopy($cropped, $source, 0, 0, $srcX, $srcY, $cropSize, $cropSize);
        imagedestroy($source);

        $resized = imagecreatetruecolor($size, $size);
        imagecopyresampled($resized, $cropped, 0, 0, 0, 0, $size, $size, $cropSize, $cropSize);
        imagedestroy($cropped);

        ob_start();
        imagejpeg($resized, null, $jpegQuality);
        imagedestroy($resized);
        $binary = ob_get_clean();

        if ($binary === false) {
            throw new RuntimeException('Unable to encode avatar image.');
        }

        $path = 'avatars/'.Str::uuid().'.jpg';
        Storage::disk('public')->put($path, $binary, 'public');

        return $path;
    }

    /**
     * @return \GdImage|resource
     */
    private function loadImage(string $path, string $extension)
    {
        $image = match ($extension) {
            'png' => imagecreatefrompng($path),
            'gif' => imagecreatefromgif($path),
            'webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : false,
            default => imagecreatefromjpeg($path),
        };

        if (! $image) {
            throw new RuntimeException('Unable to process avatar image.');
        }

        return $image;
    }
}
