<?php
declare(strict_types=1);

namespace QuickDRY\Connectors\mssql;

use QuickDRY\Connectors\TableColumn;

/**
 * Class MSSQL_TableColumn
 */
class MSSQL_TableColumn extends TableColumn
{
    public ?string $TABLE_CATALOG = null; // ""\r\n
    public ?string $TABLE_SCHEMA = null; // "dbo"\r\n
    public ?string $TABLE_NAME = null; // ""\r\n
    public ?string $COLUMN_NAME = null; // "id"\r\n
    public ?int $ORDINAL_POSITION = null; // 1\r\n
    public ?string $COLUMN_DEFAULT = null; // null\r\n
    public ?string $IS_NULLABLE = null; // "NO"\r\n
    public ?string $DATA_TYPE = null; // "int"\r\n
    public ?int $CHARACTER_MAXIMUM_LENGTH = null; // null\r\n
    public ?int $CHARACTER_OCTET_LENGTH = null; // null\r\n
    public ?int $NUMERIC_PRECISION = null; // 10\r\n
    public ?int $NUMERIC_PRECISION_RADIX = null; // 10\r\n
    public ?int $NUMERIC_SCALE = null; // 0\r\n
    public ?int $DATETIME_PRECISION = null; // null\r\n
    public ?string $CHARACTER_SET_CATALOG = null; // null\r\n
    public ?string $CHARACTER_SET_SCHEMA = null; // null\r\n
    public ?string $CHARACTER_SET_NAME = null; // null\r\n
    public ?string $COLLATION_CATALOG = null; // null\r\n
    public ?string $COLLATION_SCHEMA = null; // null\r\n
    public ?string $COLLATION_NAME = null; // null\r\n
    public ?string $DOMAIN_CATALOG = null; // null\r\n
    public ?string $DOMAIN_SCHEMA = null; // null\r\n
    public ?string $DOMAIN_NAME = null; // null

    /**
     * @param $row
     */
    public function FromRow($row): void
    {
        parent::fromData($row);

        foreach ($row as $key => $value) {
            switch ($key) {
                case 'CHARACTER_MAXIMUM_LENGTH':
                    $this->length = $value;
                    break;
                case 'COLUMN_NAME':
                    $this->field = $value;

                    if (is_numeric($value[0])) {
                        $value = 'i' . $value;
                    }
                    if (stristr($value, ' ') !== false) {
                        $value = str_replace(' ', '', $value);
                    }
                    $this->field_alias = $value;
                    break;
                case 'DATA_TYPE':
                    $this->type = $value;
                    break;
                case 'IS_NULLABLE':
                    $this->null = $value === 'YES';
                    break;
                case 'COLUMN_DEFAULT':
                    $this->default = $value;
                    break;
            }
        }

        if ($this->NUMERIC_PRECISION || $this->NUMERIC_PRECISION_RADIX) {
            $this->decimal_length = $this->NUMERIC_PRECISION . ',' . $this->NUMERIC_PRECISION_RADIX;
        } elseif ($this->CHARACTER_MAXIMUM_LENGTH) {
            $this->decimal_length = (string)$this->CHARACTER_MAXIMUM_LENGTH;
        }
    }
}