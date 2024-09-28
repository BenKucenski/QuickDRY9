<?php
namespace Bkucenski\Quickdry\JSON;

use Bkucenski\Quickdry\Utilities\strongType;

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

    public function __construct(?array $data = null, ?object $item = null)
    {
        parent::__construct($data, $item);
    }
}