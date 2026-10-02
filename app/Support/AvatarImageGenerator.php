<?php

namespace App\Support;

/** Renders a square JPEG avatar from username initials (GD). */
class AvatarImageGenerator
{
    public function render(string $username, int $size = 512): string
    {
        if (! extension_loaded('gd')) {
            throw new \RuntimeException('GD extension is required to generate avatar images.');
        }

        $initials = AvatarInitials::fromUsername($username);
        $source = $this->draw($initials, $username);
        $scaled = imagecreatetruecolor($size, $size);
        imagecopyresampled($scaled, $source, 0, 0, 0, 0, $size, $size, imagesx($source), imagesy($source));
        imagedestroy($source);

        ob_start();
        imagejpeg($scaled, null, 90);
        imagedestroy($scaled);
        $binary = ob_get_clean();

        if ($binary === false) {
            throw new \RuntimeException('Unable to encode avatar image.');
        }

        return $binary;
    }

    /** @return \GdImage */
    private function draw(string $initials, string $username): \GdImage
    {
        $size = 128;
        $image = imagecreatetruecolor($size, $size);
        $background = AvatarInitials::backgroundRgb($username);
        $bg = imagecolorallocate($image, $background['r'], $background['g'], $background['b']);
        imagefilledrectangle($image, 0, 0, $size, $size, $bg);

        $textHex = AvatarInitials::textColor($username);
        [$tr, $tg, $tb] = sscanf($textHex, '#%02x%02x%02x');
        $text = imagecolorallocate($image, $tr, $tg, $tb);

        $fontPath = $this->fontPath();
        if ($fontPath !== null) {
            $fontSize = strlen($initials) > 1 ? 48 : 56;
            $box = imagettfbbox($fontSize, 0, $fontPath, $initials);
            $textWidth = abs($box[2] - $box[0]);
            $textHeight = abs($box[7] - $box[1]);
            $x = (int) (($size - $textWidth) / 2);
            $y = (int) (($size + $textHeight) / 2);
            imagettftext($image, $fontSize, 0, $x, $y, $text, $fontPath, $initials);
        } else {
            $font = 5;
            $charWidth = imagefontwidth($font);
            $charHeight = imagefontheight($font);
            $textWidth = strlen($initials) * $charWidth;
            $x = (int) (($size - $textWidth) / 2);
            $y = (int) (($size - $charHeight) / 2);
            imagestring($image, $font, $x, $y, $initials, $text);
        }

        return $image;
    }

    private function fontPath(): ?string
    {
        foreach ([
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/TTF/DejaVuSans-Bold.ttf',
            '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
        ] as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        return null;
    }
}
