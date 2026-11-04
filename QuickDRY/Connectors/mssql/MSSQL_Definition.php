<?php
declare(strict_types=1);

namespace QuickDRY\Connectors\mssql;

 use QuickDRY\Utilities\strongType;

/**
 * Class MSSQL_Definition
 */
class MSSQL_Definition extends strongType
{
    public string $object_name;
    public string $type_desc;
    public string $definition;
}