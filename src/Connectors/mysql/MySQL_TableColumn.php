<?php

namespace Bkucenski\Quickdry\Connectors\mysql;

/**
 * Class MSSQL_TableColumn
 */
class MySQL_TableColumn
{
    public ?string $field;
    public ?string $field_alias;
    public ?string $type;
    public ?string $null;
    public ?string $default;
    public ?string $length = null;

    /**
     * @param array $row
     */
    public function FromRow(array $row): void
    {
        foreach ($row as $key => $value) {
            switch ($key) {
                case 'Field':
                    $this->field = $value;

                    if (is_numeric($value[0])) {
                        $value = 'i' . $value;
                    }
                    if (stristr($value, ' ') !== false) {
                        $value = str_replace(' ', '', $value);
                    }
                    $this->field_alias = $value;
                    break;
                case 'Type':
                    $this->type = $value;
                    break;
                case 'Null':
                    $this->null = $value === 'YES';
                    break;
                case 'Default':
                    $this->default = $value;
                    break;
            }
        }
    }
}