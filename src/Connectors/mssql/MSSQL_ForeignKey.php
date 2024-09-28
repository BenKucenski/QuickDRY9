<?php

namespace Bkucenski\Quickdry\Connectors\mssql;

/**
 * Class MSSQL_ForeignKey
 */
class MSSQL_ForeignKey
{
    public string $database_name;
    public string $table_name;

    /* @var mixed $column_name */
    public $column_name;

    public string $foreign_table_name;
    public array $foreign_column_name;
    public string $FK_CONSTRAINT_NAME;
    public string $REFERENCED_CONSTRAINT_NAME;
    public string $REFERENCED_COLUMN_ID;

    /**
     * @param $row
     */
    public function FromRow($row): void
    {
        foreach ($row as $key => $value) {
            switch ($key) {
                case 'FK_TABLE_NAME':
                    $this->table_name = $value;
                    break;
                case 'FK_DATABASE_NAME':
                    $this->database_name = $value;
                    break;
                case 'FK_COLUMN_NAME':
                    $this->column_name = $value;
                    break;
                case 'REFERENCED_TABLE_NAME':
                    $this->foreign_table_name = $value;
                    break;
                case 'REFERENCED_COLUMN_NAME':
                    $this->foreign_column_name = $value;
                    break;
                case 'FK_CONSTRAINT_NAME':
                    $this->FK_CONSTRAINT_NAME = $value;
                    break;
                case 'REFERENCED_CONSTRAINT_NAME':
                    $this->REFERENCED_CONSTRAINT_NAME = $value;
                    break;
                case 'REFERENCED_COLUMN_ID':
                    $this->REFERENCED_COLUMN_ID = $value;
                    break;
            }
        }
    }

    /**
     * @param $row
     * @return void
     */
    public function AddRow($row): void
    {
        if (!is_array($this->column_name)) {
            $this->column_name = [$this->column_name];
        }

        $this->column_name[$row['REFERENCED_COLUMN_ID']] = $row['FK_COLUMN_NAME'];
        $this->foreign_column_name[$row['REFERENCED_COLUMN_ID']] = $row['REFERENCED_COLUMN_NAME'];
        ksort($this->column_name);
        ksort($this->foreign_column_name);
    }
}