<?php
declare(strict_types=1);

namespace QuickDRY\Connectors;

use SQLite3;

/**
 *
 */
class EasySQLite extends SQLite3
{
    public string $file;

    /**
     * EasySQLite constructor.
     * @param string $file
     */
    public function __construct(string $file)
    {
        $this->file = $file;
        parent::__construct($file);
        //$this->open($file);
    }

    /**
     * @param string $sql
     * @param array|null $params
     * @return array
     */
    private function DoPreparedQuery(string $sql, ?array $params = null): array
    {
        $statement = $this->prepare($sql);
        foreach ($params as $k => $v) {
            $statement->bindValue(':' . $k, $v);
        }
        $res = $statement->execute();
        $list = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $list[] = $row;
        }
        return $list;
    }

    /**
     * @param string $sql
     * @param array|null $params
     * @return array
     */
    public function DoQuery(string $sql, ?array $params = null): array
    {
        if ($params) {
            return $this->DoPreparedQuery($sql, $params);
        }
        $res = $this->query($sql);
        if ($this->lastErrorCode()) {
            Exception([
                'sql' => $sql
                , 'params' => $params
                , 'lastErrorMsg' => $this->lastErrorMsg()
                , 'lastErrorCode' => $this->lastErrorCode()
            ]);
        }
        $list = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $list[] = $row;
        }
        return $list;
    }

    /**
     * @return array
     */
    public function GetTables(): array
    {
        $sql = '
SELECT name FROM sqlite_master
WHERE type = :type
        ';
        $res = $this->DoQuery($sql, ['type' => 'table']);
        $tables = [];
        foreach ($res as $row) {
            $tables[] = $row['name'];
        }
        return $tables;
    }
}