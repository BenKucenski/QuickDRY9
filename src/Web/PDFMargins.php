<?php

namespace Bkucenski\Quickdry\Web;

use Bkucenski\Quickdry\Utilities\strongType;

/**
 *
 */
class PDFMargins extends strongType
{
    public ?string $Units;
    public ?string $Top;
    public ?string $Left;
    public ?string $Right;
    public ?string $Bottom;
}