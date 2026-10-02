<?php

namespace App\Services;

use App\Support\MediaSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/** Uploads images, generates responsive variants, and builds public URLs. */
class ImageService
{
    /**
     * Store an uploaded image and generate configured width variants.
     *
     * Falls back to storing the original only when the GD extension is unavailable.
     *
     * @return array{path: string, variants: array<string, string>}
     */
    public function processUpload(
        UploadedFile $file,
        string $directory,
        string $preset = MediaSettings::PRESET_POST,
    ): array {
        if (! extension_loaded('gd') || ! MediaSettings::resizeEnabled($preset)) {
            return $this->storeOriginalOnly($file, $directory);
        }

        $disk = Storage::disk(config('media.disk'));
        $id = (string) Str::uuid(); // unique folder per upload
        $basePath = trim(config('media.path_prefix'), '/').'/'.$directory.'/'.$id;
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) ? $extension : 'jpg'; // normalize
        $jpegQuality = MediaSettings::jpegQuality($preset);

        $source = $this->loadImage($file->getRealPath(), $extension);
        $width = imagesx($source);
        $height = imagesy($source);

        [$working, $width, $height, $fitted] = $this->fitToMaxBox(
            $source,
            $width,
            $height,
            MediaSettings::maxWidth($preset),
            MediaSettings::maxHeight($preset),
        );

        if ($fitted) {
            imagedestroy($source);
        }

        $originalPath = $basePath.'/original.'.$extension;
        $disk->put($originalPath, $this->encodeImage($working, $extension, $jpegQuality), 'public');

        $variants = ['original' => $originalPath]; // name => storage path

        foreach (MediaSettings::variants($preset) as $name => $targetWidth) {
            if ($width <= $targetWidth) {
                continue;
            }

            $resized = $this->resizeToWidth($working, $width, $height, $targetWidth);
            $variantPath = $basePath.'/'.$name.'.'.$extension;
            $disk->put($variantPath, $this->encodeImage($resized, $extension, $jpegQuality), 'public');
            imagedestroy($resized);
            $variants[$name] = $variantPath;
        }

        imagedestroy($working);

        $primary = $variants['lg'] ?? $variants['md'] ?? $variants['sm'] ?? $variants['original']; // default img src

        return [
            'path' => $primary,
            'variants' => $variants,
        ];
    }

    /** Resolve a storage path to a public URL, or null when the path is empty. */
    public function url(?string $path, bool $absolute = false): ?string
    {
        if (! $path) {
            return null;
        }

        $diskName = config('media.disk');

        if (config("filesystems.disks.{$diskName}.driver") === 'local') {
            $relative = '/storage/'.ltrim($path, '/');

            return $absolute ? url($relative) : $relative;
        }

        return Storage::disk($diskName)->url($path);
    }

    /** Build an HTML srcset string from named variant paths. */
    public function responsiveSrcset(
        ?array $variants,
        string $preset = MediaSettings::PRESET_POST,
    ): ?string {
        if (! $variants) {
            return null;
        }

        $widths = MediaSettings::variants($preset); // sm/md/lg => pixel width
        $parts = []; // "url 800w" fragments for srcset attribute

        foreach ($variants as $name => $path) {
            if ($name === 'original' || ! isset($widths[$name])) {
                continue;
            }

            $url = $this->url($path);

            if ($url) {
                $parts[] = $url.' '.$widths[$name].'w';
            }
        }

        return $parts === [] ? null : implode(', ', $parts);
    }

    /** Delete all stored paths in a variants map from the media disk. */
    public function deleteVariants(?array $variants): void
    {
        if (! $variants) {
            return;
        }

        $disk = Storage::disk(config('media.disk'));

        foreach ($variants as $path) {
            if ($path) {
                $disk->delete($path);
            }
        }
    }

    /**
     * Store the file as-is when GD is unavailable (no variant generation).
     *
     * @return array{path: string, variants: array<string, string>}
     */
    private function storeOriginalOnly(UploadedFile $file, string $directory): array
    {
        $path = $file->store(
            trim(config('media.path_prefix'), '/').'/'.$directory,
            config('media.disk'),
        );

        return [
            'path' => $path,
            'variants' => ['original' => $path],
        ];
    }

    /**
     * Load a GD image resource from disk by file extension.
     *
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
            throw new RuntimeException('Unable to process image.');
        }

        return $image;
    }

    /**
     * Downscale to fit within a max width/height box, preserving aspect ratio.
     *
     * @param  \GdImage|resource  $source
     * @return array{0: \GdImage|resource, 1: int, 2: int, 3: bool}
     */
    private function fitToMaxBox($source, int $width, int $height, int $maxWidth, int $maxHeight): array
    {
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return [$source, $width, $height, false];
        }

        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));

        return [
            $this->resizeToDimensions($source, $width, $height, $targetWidth, $targetHeight),
            $targetWidth,
            $targetHeight,
            true,
        ];
    }

    /**
     * Proportionally downscale a source image to the target width.
     *
     * @param  \GdImage|resource  $source
     */
    private function resizeToWidth($source, int $width, int $height, int $targetWidth)
    {
        $targetHeight = (int) round($height * ($targetWidth / $width)); // preserve aspect ratio

        return $this->resizeToDimensions($source, $width, $height, $targetWidth, $targetHeight);
    }

    /**
     * @param  \GdImage|resource  $source
     */
    private function resizeToDimensions($source, int $width, int $height, int $targetWidth, int $targetHeight)
    {
        $resized = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($targetHeight > 0 && $targetWidth > 0) {
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        }

        return $resized;
    }

    /**
     * Encode a GD image to a binary string for storage.
     *
     * @param  \GdImage|resource  $image
     */
    private function encodeImage($image, string $extension, int $jpegQuality): string
    {
        ob_start();

        match ($extension) {
            'png' => imagepng($image, null, 6),
            'gif' => imagegif($image),
            'webp' => function_exists('imagewebp')
                ? imagewebp($image, null, $jpegQuality)
                : imagejpeg($image, null, $jpegQuality),
            default => imagejpeg($image, null, $jpegQuality),
        };

        return (string) ob_get_clean();
    }
}
