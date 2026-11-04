<?php
declare(strict_types=1);

namespace QuickDRY\Connectors\mysql;

use QuickDRY\Connectors\QueryExecuteResult;
use QuickDRY\Utilities\Log;

/**
 *
 */
class MySQL_Queue
{
    private array $_sql = [];

    /**
     * @return int
     */
    public function Count(): int
    {
        return sizeof($this->_sql);
    }

    /**
     * @return QueryExecuteResult|null
     */
    public function Flush(): ?QueryExecuteResult
    {
        if (!$this->Count()) {
            return null;
        }

        $sql = implode(PHP_EOL . ';' . PHP_EOL, $this->_sql);
        $res = MySQL_A::Execute($sql, null, true);
        if ($res->error) {
            Log::Insert($res);
            exit();
        }

        $this->_sql = [];

        return $res;
    }

    /**
     * @param $sql
     * @param $params
     *
     * @return int
     */
    public function Queue($sql, $params): int
    {
        $this->_sql[] = MySQL_A::EscapeQuery($sql, $params);

        if ($this->Count() > 500) {
            $c = $this->Count();
            $this->Flush();
            return $c;
        }
        return 0;
    }
}