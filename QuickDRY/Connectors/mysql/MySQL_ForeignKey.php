<?php
declare(strict_types=1);

namespace QuickDRY\Connectors\mysql;

/**
 * Class MySQL_ForeignKey
 */
class MySQL_ForeignKey
{
    public ?string $table_name = null;

    /* @var mixed $column_name */
    public mixed $column_name = null;

    public ?string $foreign_table_name = null;

    /* @var mixed $foreign_column_name */
    public mixed $foreign_column_name = null;

    public ?string $FK_CONSTRAINT_NAME = null;

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
