<?php

namespace Bkucenski\Quickdry\Utilities;



use Bkucenski\Quickdry\Utilities\strongType;

/**
 *
 */
class Chart
{
    public float $width;
    public float $height;
    public string $im;
    public string $title;
    public string $cur_color;
    public float $cur_x;
    public float $cur_y;
    public string $cur_font;

    public float $chart_x;
    public float $chart_y;
    public float $chart_width;
    public float $chart_height;

    /**
     * @param $width
     * @param $height
     */
    public function __construct($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
        $this->im = imagecreatetruecolor($width, $height);
        imagefill($this->im, 0, 0, $this->GetColor(255, 255, 255));
    }

    /**
     * @param $x
     * @param $y
     * @param $width
     * @param $height
     * @return void
     */
    public function SetChart($x, $y, $width, $height): void
    {
        $this->chart_x = $x;
        $this->chart_y = $y;
        $this->chart_width = $width;
        $this->chart_height = $height;
    }

    /**
     * @param $x
     * @param $y
     * @return void
     */
    public function SetStartPoint($x, $y): void
    {
        $this->cur_x = $x;
        $this->cur_y = $this->height - $y;
    }

    /**
     * @param $x
     * @param $y
     * @return void
     */
    public function SetStartPointRatio($x, $y): void
    {
        $x = floor($x * $this->width);
        $y = floor($y * $this->height);
        $this->SetStartPoint($x, $y);
    }

    /**
     * @param $x
     * @param $y
     * @param $width
     * @param $height
     * @return void
     */
    public function PlotPointRatio($x, $y, $width, $height = null): void
    {
        if (is_null($height))
            $height = $width;

        $x = floor($x * $this->width);
        $y = floor((1.0 - $y) * $this->height);
        imagearc($this->im, $x, $y, $width, $height, 0, 360, $this->cur_color);
    }

    /**
     * @param $x
     * @param $y
     * @param $width
     * @param $height
     * @return void
     */
    public function PlotChartPointRatio($x, $y, $width, $height = null): void
    {
        if (is_null($height))
            $height = $width;

        $x = floor($x * $this->chart_width) + $this->chart_x;
        $y = floor((1.0 - $y) * $this->chart_height) + $this->chart_y;
        imagearc($this->im, $x, $y, $width, $height, 0, 360, $this->cur_color);
    }

    /**
     * @param $x
     * @param $y
     * @return void
     */
    public function LineToRatio($x, $y): void
    {
        $x = floor($x * $this->width);
        $y = floor((1.0 - $y) * $this->height);

        imageline($this->im, $x, $y, $this->cur_x, $this->cur_y, $this->cur_color);
    }

    /**
     * @param $r
     * @param $g
     * @param $b
     * @return void
     */
    public function SetColor($r, $g, $b): void
    {
        $this->cur_color = $this->GetColor($r, $g, $b);
    }

    /**
     * @param $x
     * @return void
     */
    public function SetFont($x): void
    {
        $this->cur_font = $x;
    }

    /**
     * @param $str
     * @param $x
     * @param $y
     * @return void
     */
    public function WriteRatio($str, $x, $y): void
    {
        $x = floor($x * $this->width);
        $y = floor((1.0 - $y) * $this->height);
        imagestring($this->im, $this->cur_font, $x, $y, $str, $this->cur_color);
    }

    /**
     * @param $str
     * @param $x
     * @param $y
     * @return void
     */
    public function WriteChartRatio($str, $x, $y): void
    {
        $x = floor($x * $this->chart_width) + $this->chart_x;
        $y = floor((1.0 - $y) * $this->chart_height) + $this->chart_y;
        imagestring($this->im, $this->cur_font, $x, $y, $str, $this->cur_color);
    }


    /**
     * @param $r
     * @param $g
     * @param $b
     * @return false|int
     */
    private function GetColor($r, $g, $b): bool|int
    {
        return imagecolorallocate($this->im, $r, $g, $b);
    }

    /**
     * @return bool
     */
    public function GetJpeg(): bool
    {
        imagestring($this->im, 5, 1, 1, strtoupper($this->title), $this->GetColor(0, 0, 0));

        return imagejpeg($this->im);
    }
}
