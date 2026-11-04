<?php
declare(strict_types=1);

namespace QuickDRY\Math;

use QuickDRY\Utilities\strongType;

/**
 * Class Debt
 */
class Debt extends strongType
{
    public ?int $id = null;
    public ?float $interest_rate = null;
    public ?float $payment = null;
    public ?float $principal = null;
    public ?string $name = null;
}