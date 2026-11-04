<?php
declare(strict_types=1);

namespace QuickDRY\Connectors\mssql;

use DateTime;
use QuickDRY\Utilities\strongType;

/**
 *
 */
class MSSQL_StoredProc extends strongType
{
    public ?string $SPECIFIC_CATALOG;
    public ?string $SPECIFIC_SCHEMA;
    public ?string $SPECIFIC_NAME;
    public ?string $ROUTINE_CATALOG;
    public ?string $ROUTINE_SCHEMA;
    public ?string $ROUTINE_NAME;
    public ?string $ROUTINE_TYPE;
    public ?string $MODULE_CATALOG;
    public ?string $MODULE_SCHEMA;
    public ?string $MODULE_NAME;
    public ?string $UDT_CATALOG;
    public ?string $UDT_SCHEMA;
    public ?string $UDT_NAME;
    public ?string $DATA_TYPE;
    public ?string $CHARACTER_MAXIMUM_LENGTH;
    public ?string $CHARACTER_OCTET_LENGTH;
    public ?string $COLLATION_CATALOG;
    public ?string $COLLATION_SCHEMA;
    public ?string $COLLATION_NAME;
    public ?string $CHARACTER_SET_CATALOG;
    public ?string $CHARACTER_SET_SCHEMA;
    public ?string $CHARACTER_SET_NAME;
    public ?string $NUMERIC_PRECISION;
    public ?string $NUMERIC_PRECISION_RADIX;
    public ?string $NUMERIC_SCALE;
    public ?string $DATETIME_PRECISION;
    public ?string $INTERVAL_TYPE;
    public ?string $INTERVAL_PRECISION;
    public ?string $TYPE_UDT_CATALOG;
    public ?string $TYPE_UDT_SCHEMA;
    public ?string $TYPE_UDT_NAME;
    public ?string $SCOPE_CATALOG;
    public ?string $SCOPE_SCHEMA;
    public ?string $SCOPE_NAME;
    public ?string $MAXIMUM_CARDINALITY;
    public ?string $DTD_IDENTIFIER;
    public ?string $ROUTINE_BODY;
    public ?string $ROUTINE_DEFINITION;
    public ?string $EXTERNAL_NAME;
    public ?string $EXTERNAL_LANGUAGE;
    public ?string $PARAMETER_STYLE;
    public ?string $IS_DETERMINISTIC;
    public ?string $SQL_DATA_ACCESS;
    public ?string $IS_NULL_CALL;
    public ?string $SQL_PATH;
    public ?string $SCHEMA_LEVEL_ROUTINE;
    public ?int $MAX_DYNAMIC_RESULT_SETS;
    public ?string $IS_USER_DEFINED_CAST;
    public ?string $IS_IMPLICITLY_INVOCABLE;
    public ?DateTime $CREATED;
    public ?DateTime $LAST_ALTERED;
    public ?string $SOURCE_CODE;

}