<?php

namespace Bkucenski\Quickdry\Connectors\mssql;

/**
 * Class MSSQL_TableColumn
 */
class MSSQL_TableColumn
{
    public ?string $field;
    public ?string $field_alias;
    public ?string $type;
    public ?string $null;
    public ?string $default;
    public ?int $length = null;

    /**
     * @param $row
     */
    public function FromRow($row): void
    {
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
    }
}