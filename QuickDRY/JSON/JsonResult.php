<?php
declare(strict_types=1);

namespace QuickDRY\JSON;

use QuickDRY\Utilities\strongType;

/**
 *
 */
class JsonResult extends strongType
{
    public ?string $ContentEncoding = null;
    public ?string $ContentType = null;
    public ?string $Data = null;
    public ?string $JsonRequestBehavior = null;
    public ?int $MaxJsonLength = null;
    public ?int $RecursionLimit = null;

    public function __construct()
    {
    }
}