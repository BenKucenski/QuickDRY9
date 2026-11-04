<?php
declare(strict_types=1);

namespace QuickDRY\Connectors;

use QuickDRY\Utilities\strongType;

/**
 * Class SQL_Query
 *
 * @property array $Params
 * @property string SQL
 */
class SQL_Query extends strongType
{
    public string $SQL;
    public ?array $Params;

    /**
     * @param string $sql
     * @param array|null $params
     */
    public function __construct(string $sql, ?array $params = null)
    {
        $this->SQL = $sql;
        $this->Params = $params;
    }
}