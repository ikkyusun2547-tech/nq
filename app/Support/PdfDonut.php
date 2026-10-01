<?php

namespace App\Support;

/**
 * dompdf can't draw arcs (no SVG paths, no conic-gradient), so the progress
 * ring on the activity-summary PDF is rasterised here with GD and embedded
 * as a data URI. Drawn at 4x and downsampled, since GD's arcs aren't
 * anti-aliased.
 */
class PdfDonut
{
    /**
     * @param  array{0: int, 1: int, 2: int}  $background  what the ring sits on (no alpha in PDFs)
     * @param  array{0: int, 1: int, 2: int}  $track
     * @param  array{0: int, 1: int, 2: int}  $fill
     */
    public static function dataUri(
        float $percent,
        string $label,
        array $background,
        array $track,
        array $fill,
        int $size = 240,
        float $thickness = 0.14,
    ): string {
        $scale = 4;
        $big = $size * $scale;
        $hi = imagecreatetruecolor($big, $big);
        imagefill($hi, 0, 0, imagecolorallocate($hi, ...$background));

        $center = intdiv($big, 2);
        $outer = $big - 8 * $scale;
        $inner = (int) round($outer * (1 - 2 * $thickness));

        imagefilledellipse($hi, $center, $center, $outer, $outer, imagecolorallocate($hi, ...$track));
        $percent = max(0, min(100, $percent));
        if ($percent > 0) {
            $end = -90 + (int) round(360 * $percent / 100);
            imagefilledarc($hi, $center, $center, $outer, $outer, -90, max(-89, $end), imagecolorallocate($hi, ...$fill), IMG_ARC_PIE);
        }
        imagefilledellipse($hi, $center, $center, $inner, $inner, imagecolorallocate($hi, ...$background));

        $image = imagecreatetruecolor($size, $size);
        imagecopyresampled($image, $hi, 0, 0, 0, 0, $size, $size, $big, $big);
        imagedestroy($hi);

        // Centre label, drawn at final size so the glyphs stay anti-aliased.
        // Skipped (plain ring) on a GD build without FreeType.
        if (! function_exists('imagettftext')) {
            return self::encode($image);
        }
        $font = resource_path('fonts/Sarabun-Bold.ttf');
        $fontSize = $size * 0.15;
        $box = imagettfbbox($fontSize, 0, $font, $label);
        $textWidth = $box[2] - $box[0];
        $textHeight = $box[1] - $box[7];
        imagettftext(
            $image, $fontSize, 0,
            (int) round(($size - $textWidth) / 2 - $box[0]),
            (int) round(($size + $textHeight) / 2 - $box[1]),
            imagecolorallocate($image, ...$fill), $font, $label,
        );

        return self::encode($image);
    }

    private static function encode(\GdImage $image): string
    {
        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
