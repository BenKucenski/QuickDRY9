<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;


use GdFont;

/**
 *
 */
class Chart
{
    public ?float $width = null;
    public ?float $height = null;
    public mixed $im = null;
    public ?string $title = null;
    public ?int $cur_color = null;
    public ?float $cur_x = null;
    public ?float $cur_y = null;
    public ?GdFont $cur_font = null;

    public ?float $chart_x = null;
    public ?float $chart_y = null;
    public ?float $chart_width = null;
    public ?float $chart_height = null;

    /**
     * @param int $width
     * @param int $height
     */
    public function __construct(int $width, int $height)
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
     * @param float $x
     * @param float $y
     * @param int $width
     * @param int|null $height
     * @return void
     */
    public function PlotPointRatio(float $x, float $y, int $width, ?int $height = null): void
    {
        if (is_null($height)) {
            $height = $width;
        }

        $x = (int)floor($x * $this->width);
        $y = (int)floor((1.0 - $y) * $this->height);
        imagearc($this->im, $x, $y, $width, $height, 0, 360, $this->cur_color);
    }

    /**
     * @param float $x
     * @param float $y
     * @param int $width
     * @param int|null $height
     * @return void
     */
    public function PlotChartPointRatio(float $x, float $y, int $width, ?int $height = null): void
    {
        if (is_null($height))
            $height = $width;

        $x = (int)floor($x * $this->chart_width) + $this->chart_x;
        $y = (int)floor((1.0 - $y) * $this->chart_height) + $this->chart_y;
        imagearc($this->im, $x, $y, $width, $height, 0, 360, $this->cur_color);
    }

    /**
     * @param $x
     * @param $y
     * @return void
     */
    public function LineToRatio($x, $y): void
    {
        $x = (int)floor($x * $this->width);
        $y = (int)floor((1.0 - $y) * $this->height);

        imageline($this->im, $x, $y, (int)$this->cur_x, (int)$this->cur_y, $this->cur_color);
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
        $x = (int)floor($x * $this->width);
        $y = (int)floor((1.0 - $y) * $this->height);
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
        $x = (int)floor($x * $this->chart_width) + $this->chart_x;
        $y = (int)floor((1.0 - $y) * $this->chart_height) + $this->chart_y;
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
