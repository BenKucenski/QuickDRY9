<?php
declare(strict_types=1);

namespace QuickDRY\Connectors\mysql;

use QuickDRY\Connectors\TableColumn;

/**
 * Class MSSQL_TableColumn
 */
class MySQL_TableColumn extends TableColumn
{
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