<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;

/**
 * Class Color
 */
class Color extends strongType
{
    public ?float $r;
    public ?float $g;
    public ?float $b;

    /**
     * @param float $r
     * @param float $g
     * @param float $b
     */
    public function __construct(float $r, float $g, float $b)
    {
        $this->r = $r;
        $this->g = $g;
        $this->b = $b;
    }

    /**
     * @return float
     */
    public function Brightness(): float
    {
        return sqrt(
            $this->r * $this->r * .241 +
            $this->g * $this->g * .691 +
            $this->b * $this->b * .068);
    }

    // http://www.nbdtech.com/Blog/archive/2008/04/27/Calculating-the-Perceived-Brightness-of-a-Color.aspx
    //https://bavotasan.com/2011/convert-hex-color-to-rgb-using-php/
    /**
     * @param $hex
     * @return Color
     */
    public static function HexToRGB($hex): Color
    {
        $hex = str_replace('#', '', $hex);

        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        //return implode(",", $rgb); // returns the rgb values separated by commas
        return new self($r, $g, $b); // returns an array with the rgb values
    }

    /**
     * @param Color $rgb
     * @return string
     */
    public static function RGBToHex(Color $rgb): string
    {
        $hex = '#';
        $hex .= str_pad(dechex((int)$rgb->r), 2, '0', STR_PAD_LEFT);
        $hex .= str_pad(dechex((int)$rgb->g), 2, '0', STR_PAD_LEFT);
        $hex .= str_pad(dechex((int)$rgb->b), 2, '0', STR_PAD_LEFT);

        return $hex; // returns the hex value including the number sign (#)
    }


}