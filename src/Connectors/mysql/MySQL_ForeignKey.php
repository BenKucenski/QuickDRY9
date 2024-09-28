<?php

namespace Bkucenski\Quickdry\Connectors\mysql;

/**
 * Class MySQL_ForeignKey
 */
class MySQL_ForeignKey
{
    public ?string $table_name;

    /* @var mixed $column_name */
    public $column_name;

    public ?string $foreign_table_name;

    /* @var mixed $foreign_column_name */
    public $foreign_column_name;

    public ?string $FK_CONSTRAINT_NAME;

    /**
     * @param array $row
     */
    public function FromRow(array $row): void
    {
        foreach ($row as $key => $value) {
            switch ($key) {
                case 'table_name':
                    $this->table_name = $value;
                    break;
                case 'column_name':
                    $this->column_name = $value;
                    break;
                case 'referenced_table_name':
                    $this->foreign_table_name = $value;
                    break;
                case 'referenced_column_name':
                    $this->foreign_column_name = $value;
                    break;
                case 'CONSTRAINT_NAME':
                    $this->FK_CONSTRAINT_NAME = $value;
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

        if (!is_array($this->foreign_column_name)) {
            $this->foreign_column_name = [$this->foreign_column_name];
        }

        $this->column_name[] = $row['column_name'];
        $this->foreign_column_name[] = $row['referenced_column_name'];
    }
}
