<?php

namespace Bkucenski\Quickdry\Connectors\mssql;

use Bkucenski\Quickdry\Utilities\SimpleReport;

/**
 * Class MSSQL_Definition
 */
class MSSQL_Definition extends SimpleReport
{
    public string $object_name;
    public string $type_desc;
    public string $definition;
}