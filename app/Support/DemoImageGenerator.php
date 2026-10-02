<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/** Generates colorful placeholder JPEGs for demo seeding (GD). */
class DemoImageGenerator
{
    public function uploadedFile(
        string $label,
        int $width = 1200,
        int $height = 675,
    ): UploadedFile {
        if (! extension_loaded('gd')) {
            throw new \RuntimeException('GD extension is required to generate demo images.');
        }

        $path = tempnam(sys_get_temp_dir(), 'magazines-demo-');
        if ($path === false) {
            throw new \RuntimeException('Unable to create temporary demo image.');
        }

        $jpegPath = $path.'.jpg';
        rename($path, $jpegPath);

        $image = $this->draw($label, $width, $height);
        imagejpeg($image, $jpegPath, 88);
        imagedestroy($image);

        register_shutdown_function(static fn () => @unlink($jpegPath));

        return new UploadedFile($jpegPath, 'demo.jpg', 'image/jpeg', null, true);
    }

    /** Store a square avatar on the public disk; returns the storage path. */
    public function storeAvatar(string $label, string $directory = 'avatars'): string
    {
        $file = $this->uploadedFile($label, 512, 512);

        return $file->store($directory, config('media.disk', 'public'));
    }

    /** @return \GdImage */
    private function draw(string $label, int $width, int $height): \GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        [$r1, $g1, $b1, $r2, $g2, $b2] = $this->palette($label);

        for ($y = 0; $y < $height; $y++) {
            $ratio = $height > 1 ? $y / ($height - 1) : 0;
            $r = (int) ($r1 + ($r2 - $r1) * $ratio);
            $g = (int) ($g1 + ($g2 - $g1) * $ratio);
            $b = (int) ($b1 + ($b2 - $b1) * $ratio);
            $line = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $y, $width, $y, $line);
        }

        $accent = imagecolorallocatealpha($image, 255, 255, 255, 90);
        $seed = crc32($label);
        for ($i = 0; $i < 4; $i++) {
            $cx = (int) (($seed >> ($i * 4)) % max(1, $width - 200)) + 100;
            $cy = (int) (($seed >> ($i * 6 + 2)) % max(1, $height - 200)) + 100;
            $diameter = 120 + (($seed >> ($i * 3)) % 180);
            imagefilledellipse($image, $cx, $cy, $diameter, $diameter, $accent);
        }

        $textColor = imagecolorallocate($image, 255, 255, 255);
        $shadow = imagecolorallocatealpha($image, 0, 0, 0, 40);
        $lines = $this->wrap($label, $width > 900 ? 34 : 22);
        $lineHeight = 18;
        $blockHeight = count($lines) * $lineHeight;
        $startY = (int) (($height - $blockHeight) / 2);

        foreach ($lines as $index => $line) {
            $x = 48;
            $y = $startY + ($index * $lineHeight);
            imagestring($image, 5, $x + 2, $y + 2, $line, $shadow);
            imagestring($image, 5, $x, $y, $line, $textColor);
        }

        return $image;
    }

    /** @return array{int, int, int, int, int, int} */
    private function palette(string $label): array
    {
        $hash = crc32($label);

        return [
            80 + ($hash % 120),
            90 + (($hash >> 8) % 100),
            110 + (($hash >> 16) % 90),
            30 + (($hash >> 4) % 100),
            40 + (($hash >> 12) % 120),
            70 + (($hash >> 20) % 110),
        ];
    }

    /** @return list<string> */
    private function wrap(string $text, int $maxChars): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;
            if (strlen($candidate) > $maxChars && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines !== [] ? $lines : [substr($text, 0, $maxChars)];
    }
}
