<?php
declare(strict_types=1);

namespace QuickDRY\Connectors\mysql;

use QuickDRY\Utilities\strongType;

/**
 *
 */
class MySQL_StoredProcParam extends strongType
{
    public ?string $SPECIFIC_CATALOG;
    public ?string $SPECIFIC_SCHEMA;
    public ?string $SPECIFIC_NAME;
    public ?string $ORDINAL_POSITION;
    public ?string $PARAMETER_MODE;
    public ?string $PARAMETER_NAME;
    public ?string $DATA_TYPE;
    public ?string $CHARACTER_MAXIMUM_LENGTH;
    public ?string $CHARACTER_OCTET_LENGTH;
    public ?string $NUMERIC_PRECISION;
    public ?string $NUMERIC_SCALE;
    public ?string $DATETIME_PRECISION;
    public ?string $CHARACTER_SET_NAME;
    public ?string $COLLATION_NAME;
    public ?string $DTD_IDENTIFIER;
    public ?string $ROUTINE_TYPE;

    /**
     * @param $row
     */
    public function __construct($row = null)
    {
        if ($row) {
            $this->fromData($row);
        }
    }
}