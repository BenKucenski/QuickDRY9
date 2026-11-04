<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;

/**
 *
 */
class ParsedUSAddress extends strongType
{
    public ?string $street = '';
    public ?string $city   = '';
    public ?string $state  = '';
    public ?string $zip    = '';
}