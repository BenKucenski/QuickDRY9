<?php
declare(strict_types=1);

namespace QuickDRY\Connectors\mssql;

use QuickDRY\Connectors\MSSQL;
use QuickDRY\Connectors\QueryExecuteResult;
use QuickDRY\Connectors\SQL_Query;
use QuickDRY\Utilities\Log;
use QuickDRY\Utilities\Metrics;
use QuickDRY\Utilities\strongType;

/**
 *
 */
class MSSQL_Queue extends strongType
{
    private array $_sql = [];
    private int $strlen = 0;
    private string $MSSQL_CLASS;
    public string $LogClass = Log::class;
    public bool $HaltOnError;
    public bool $IgnoreDuplicateError = false;
    private int $QueueLimit;

    /**
     * @param $MSSQL_CLASS
     * @param $HaltOnError
     * @param int $QueueLimit
     */
    public function __construct($MSSQL_CLASS = MSSQL_A::class, $HaltOnError = true, int $QueueLimit = 500)
    {
        $this->MSSQL_CLASS = $MSSQL_CLASS;
        $this->HaltOnError = $HaltOnError;
        $this->QueueLimit = $QueueLimit;
    }

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

        Metrics::Toggle('MSSQL_Queue::Flush');
        $sql = implode(';' . "\n", $this->_sql);
        $sql = trim('
SET QUOTED_IDENTIFIER ON
;
        		' . $sql . ' ;');

        $class = $this->MSSQL_CLASS;
        $class::SetIgnoreDuplicateError($this->IgnoreDuplicateError);

        $res = $class::Execute($sql, null, true);

        Metrics::Toggle('MSSQL_Queue::Flush');
        if ($res->error && $this->HaltOnError) {
            $LogClass = $this->LogClass;

            if (!method_exists($LogClass, 'Insert')) {
                Exception("$LogClass::Insert");
            }

            $LogClass::Insert(['MSSQL_Queue Error' => $res]);
            Exception($res);
        }

        $this->_sql = [];
        $this->strlen = 0;

        return $res;
    }

    /**
     * @param SQL_Query $sp
     * @return QueryExecuteResult|null
     */
    public function QueueSP(SQL_Query $sp): ?QueryExecuteResult
    {
        return $this->Queue($sp->SQL, $sp->Params);
    }

    /**
     * @param string $sql
     * @param array|null $params
     * @return QueryExecuteResult|null
     */
    public function Queue(string $sql, ?array $params = null): ?QueryExecuteResult
    {
        $t = MSSQL::EscapeQuery($sql, $params);
        $this->_sql[] = $t;
        $this->strlen += strlen($t);

        if ($this->strlen > 1024 * 1024 * 50 || $this->Count() >= $this->QueueLimit) {
            return $this->Flush();
        }
        return new QueryExecuteResult();
    }
}