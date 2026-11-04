<?php
declare(strict_types=1);

namespace QuickDRY\Connectors;

use QuickDRY\Utilities\strongType;

/**
 *
 */
class TableColumn extends strongType
{
    public ?string $field = null;
    public ?string $field_alias = null;
    public ?string $type = null;
    public ?bool $null = null;
    public ?string $default = null;
    public ?int $length = null;

    public ?string $decimal_length = null;
}