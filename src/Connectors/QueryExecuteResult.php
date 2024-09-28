<?php

namespace Bkucenski\Quickdry\Connectors;


use Bkucenski\Quickdry\Utilities\strongType;

/**
 *
 */
class QueryExecuteResult extends strongType
{
    public ?string $error = null;
    public ?string $sql = null;
    public ?string $exec = null;
    public ?int $last_id = null;
    public ?int $affected_rows = null;
    public ?array $log = null;

    public ?string $numrows = null; // 1\r\n
    public ?array $data = null; // []\r\n
    public ?string $query = null; // \r\n
}