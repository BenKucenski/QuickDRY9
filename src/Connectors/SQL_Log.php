<?php

namespace Bkucenski\Quickdry\Connectors;

use Bkucenski\Quickdry\Utilities\strongType;

/**
 * Class SQL_Log
 */
class SQL_Log extends strongType
{
    public ?string $source = null;
    public ?string $query = null;
    public ?array $params = null;
    public ?string $start_time = null;
    public ?string $end_time = null;
    public ?float $duration = null;
}