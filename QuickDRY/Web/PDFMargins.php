<?php
declare(strict_types=1);

namespace QuickDRY\Web;

use QuickDRY\Utilities\strongType;

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