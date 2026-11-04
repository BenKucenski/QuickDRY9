<?php
declare(strict_types=1);

namespace QuickDRY\Connectors\mysql;

use QuickDRY\Utilities\strongType;

/**
 *
 */
class MySQL_StoredProc extends strongType
{
    public ?string $SPECIFIC_CATALOG = null;
    public ?string $SPECIFIC_SCHEMA = null;
    public ?string $SPECIFIC_NAME = null;
    public ?string $ROUTINE_CATALOG = null;
    public ?string $ROUTINE_SCHEMA = null;
    public ?string $ROUTINE_NAME = null;
    public ?string $ROUTINE_TYPE = null;
    public ?string $MODULE_CATALOG = null;
    public ?string $MODULE_SCHEMA = null;
    public ?string $MODULE_NAME = null;
    public ?string $UDT_CATALOG = null;
    public ?string $UDT_SCHEMA = null;
    public ?string $UDT_NAME = null;
    public ?string $DATA_TYPE = null;
    public ?string $CHARACTER_MAXIMUM_LENGTH = null;
    public ?string $CHARACTER_OCTET_LENGTH = null;
    public ?string $COLLATION_CATALOG = null;
    public ?string $COLLATION_SCHEMA = null;
    public ?string $COLLATION_NAME = null;
    public ?string $CHARACTER_SET_CATALOG = null;
    public ?string $CHARACTER_SET_SCHEMA = null;
    public ?string $CHARACTER_SET_NAME = null;
    public ?string $NUMERIC_PRECISION = null;
    public ?string $NUMERIC_PRECISION_RADIX = null;
    public ?string $NUMERIC_SCALE = null;
    public ?string $DATETIME_PRECISION = null;
    public ?string $INTERVAL_TYPE = null;
    public ?string $INTERVAL_PRECISION = null;
    public ?string $TYPE_UDT_CATALOG = null;
    public ?string $TYPE_UDT_SCHEMA = null;
    public ?string $TYPE_UDT_NAME = null;
    public ?string $SCOPE_CATALOG = null;
    public ?string $SCOPE_SCHEMA = null;
    public ?string $SCOPE_NAME = null;
    public ?string $MAXIMUM_CARDINALITY = null;
    public ?string $DTD_IDENTIFIER = null;
    public ?string $ROUTINE_BODY = null;
    public ?string $ROUTINE_DEFINITION = null;
    public ?string $EXTERNAL_NAME = null;
    public ?string $EXTERNAL_LANGUAGE = null;
    public ?string $PARAMETER_STYLE = null;
    public ?string $IS_DETERMINISTIC = null;
    public ?string $SQL_DATA_ACCESS = null;
    public ?string $IS_NULL_CALL = null;
    public ?string $SQL_PATH = null;
    public ?string $SCHEMA_LEVEL_ROUTINE = null;
    public ?int $MAX_DYNAMIC_RESULT_SETS = null;
    public ?string $IS_USER_DEFINED_CAST = null;
    public ?string $IS_IMPLICITLY_INVOCABLE = null;
    public ?string $CREATED = null;
    public ?string $LAST_ALTERED = null;

    // extra
    public ?string $PARAMETERS = null;

}