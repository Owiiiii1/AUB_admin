<?php

namespace App\Services\SecureFiles;

use App\Exceptions\SecureFileException;

class ImageNormalizer
{
    public function __construct(
        private readonly int $jpegQuality = 88,
    ) {}

    /**
     * Decode, strip metadata, re-encode. Returns [binary, mime, extension].
     *
     * @return array{0: string, 1: string, 2: string}
     */
    public function normalize(string $binary, string $declaredMime): array
    {
        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            throw SecureFileException::invalidType();
        }

        $image = $this->applyExifOrientation($binary, $image);

        if (! imageistruecolor($image)) {
            $true = imagecreatetruecolor(imagesx($image), imagesy($image));
            if ($true === false) {
                imagedestroy($image);
                throw SecureFileException::invalidType();
            }
            imagecopy($true, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
            imagedestroy($image);
            $image = $true;
        }

        $mime = $declaredMime === 'image/png' ? 'image/png' : 'image/jpeg';
        if ($declaredMime === 'image/webp' && function_exists('imagewebp')) {
            $mime = 'image/webp';
        }

        ob_start();
        $ok = match ($mime) {
            'image/png' => imagepng($image, null, 6),
            'image/webp' => imagewebp($image, null, $this->jpegQuality),
            default => imagejpeg($image, null, $this->jpegQuality),
        };
        $encoded = (string) ob_get_clean();
        imagedestroy($image);

        if (! $ok || $encoded === '') {
            throw SecureFileException::invalidType();
        }

        $extension = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        return [$encoded, $mime, $extension];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    public function thumbnail(string $normalizedBinary, int $maxEdge): array
    {
        $image = @imagecreatefromstring($normalizedBinary);
        if ($image === false) {
            throw SecureFileException::invalidType();
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxEdge / max($width, $height, 1));
        $newW = max(1, (int) round($width * $scale));
        $newH = max(1, (int) round($height * $scale));

        $thumb = imagecreatetruecolor($newW, $newH);
        if ($thumb === false) {
            imagedestroy($image);
            throw SecureFileException::invalidType();
        }
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newW, $newH, $width, $height);
        imagedestroy($image);

        ob_start();
        $ok = imagejpeg($thumb, null, 82);
        $encoded = (string) ob_get_clean();
        imagedestroy($thumb);

        if (! $ok || $encoded === '') {
            throw SecureFileException::invalidType();
        }

        return [$encoded, 'image/jpeg', 'jpg'];
    }

    /**
     * @param  \GdImage  $image
     * @return \GdImage
     */
    private function applyExifOrientation(string $binary, $image)
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'aubexif');
        if ($tmp === false) {
            return $image;
        }

        file_put_contents($tmp, $binary);
        $exif = @exif_read_data($tmp);
        @unlink($tmp);

        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };

        if ($rotated !== false && $rotated !== null) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }
}
