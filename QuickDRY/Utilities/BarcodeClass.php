<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;

/**
 * Class BarcodeClass
 */
class BarcodeClass extends strongType
{
    /**
     * @param $root
     * @param $width
     * @param $height
     * @param $code
     * @param int|null $font_size
     * @param bool $debug
     * @return resource
     */
    public static function Generate($root, $width, $height, $code, ?int $font_size = null, bool $debug = false)
    {
        if (!str_ends_with($root, 'barcode/')) {
            $root .= 'barcode/';
        }

        $filename = $root . $width . '/' . $height . '/' . $code . '.png';

        if (file_exists($filename)) {
            return null;
        }

        $number = strtoupper(base64_decode($code));

        $barcode_font = $root . 'FREE3OF9.TTF';

        if ($debug) {
            exit($barcode_font);
        }

        if (!is_dir($root . $width)) {
            mkdir($root . $width);
        }

        if (!is_dir($root . $width . '/' . $height)) {
            mkdir($root . $width . '/' . $height);
        }

        $font_size = $font_size ?: $width / (strlen($number) / 1.9);

        $img = imagecreate($width, $height);
        // First call to imagecolorallocate is the background color
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);

        // Reference for the imagettftext() function
        // imagettftext($img, $fontsize, $angle, $xpos, $ypos, $color, $fontfile, $text);
        imagettftext($img, $font_size, 0, 0, (int)$font_size, $black, $barcode_font, $number);

        imagepng($img, $filename);
        return $img;
    }
}