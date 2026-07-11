<?php

namespace App\Support;

class PdfGradientRenderer
{
    /**
     * dompdf silently drops CSS linear-gradient backgrounds but renders a
     * background-image data URI fine, so this pre-renders a brand gradient
     * as a small raster image for use in PDF templates. Reused by both the
     * web and API transcript-download PDFs (see
     * Student\TranscriptController::headerGradientDataUri() for the
     * original, kept as-is there rather than refactored to call this).
     *
     * @param  array<int, array{0: float, 1: array{0: int, 1: int, 2: int}}>  $stops
     */
    public static function headerGradientDataUri(array $stops, int $width = 40, int $height = 1): string
    {
        $image = imagecreatetruecolor($width, $height);

        for ($x = 0; $x < $width; $x++) {
            $t = $width > 1 ? $x / ($width - 1) : 0;
            [$r, $g, $b] = self::interpolateStops($stops, $t);
            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, $x, 0, $x, $height, $color);
        }

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    /**
     * @param  array<int, array{0: float, 1: array{0: int, 1: int, 2: int}}>  $stops
     * @return array{0: int, 1: int, 2: int}
     */
    private static function interpolateStops(array $stops, float $t): array
    {
        for ($i = 0; $i < count($stops) - 1; $i++) {
            [$startT, $startColor] = $stops[$i];
            [$endT, $endColor] = $stops[$i + 1];

            if ($t >= $startT && $t <= $endT) {
                $localT = $endT > $startT ? ($t - $startT) / ($endT - $startT) : 0;

                return [
                    (int) round($startColor[0] + ($endColor[0] - $startColor[0]) * $localT),
                    (int) round($startColor[1] + ($endColor[1] - $startColor[1]) * $localT),
                    (int) round($startColor[2] + ($endColor[2] - $startColor[2]) * $localT),
                ];
            }
        }

        return end($stops)[1];
    }
}
