<?php
declare(strict_types=1);

namespace QuickDRY\Connectors\mssql;

 use QuickDRY\Utilities\strongType;

/**
 *
 */
class MSSQL_Trigger extends strongType
{
    public string $name;
    public string $object_id;
    public string $parent_class;
    public string $parent_class_desc;
    public string $parent_id;
    public string $type;
    public string $type_desc;
    public string $create_date;
    public string $modify_date;
    public string $is_ms_shipped;
    public string $is_disabled;
    public string $is_not_for_replication;
    public string $is_instead_of_trigger;
    public string $definition;
}