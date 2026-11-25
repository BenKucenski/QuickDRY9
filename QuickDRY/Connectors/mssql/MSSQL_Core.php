<?php
declare(strict_types=1);

namespace QuickDRY\Connectors\mssql;

use DateTime;
use DateTimeInterface;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use models\ChangeLog;
use QuickDRY\Connectors\MSSQL;
use QuickDRY\Connectors\QueryExecuteResult;
use QuickDRY\Connectors\SQL_Base;
use QuickDRY\Connectors\SQL_Log;
use QuickDRY\Connectors\SQL_Query;
use QuickDRY\Interfaces\ICurrentUser;
use QuickDRY\Interfaces\ISQLConnection;
use QuickDRY\Interfaces\ISQLCore;
use QuickDRY\Utilities\Dates;
use QuickDRY\Utilities\Strings;
use QuickDRY\Web\ElementID;

/**
 *
 */
class MSSQL_Core extends SQL_Base implements ISQLCore
{
    protected static ?MSSQL_Connection $connection = null;

    protected static array $prop_definitions = [];
    protected static array $_primary = [];
    protected static array $_unique = [];

    protected static string $DatabaseTypePrefix = 'ms';
    protected static string $DatabasePrefix = '';
    protected static int $LowerCaseTable = 0;
    protected static ?string $DB_HOST = null;
    protected bool $PRESERVE_NULL_STRINGS = false;  // when true, if a property is set to the string 'null' it will be inserted as 'null' rather than null

    /**
     * @param string|null $selected
     * @param ElementID|null $id
     * @return string
     */
    public static function Select(?string $selected = null, ?ElementID $id = null): string
    {
        return print_r(['Select Not Implemented', $selected, $id], true);
    }

    /**
     * @param string $column_name
     * @return string
     */
    public static function ColumnNameToNiceName(string $column_name): string
    {
        return isset(static::$prop_definitions[$column_name]) ? static::$prop_definitions[$column_name]['display'] : '<i>unknown</i>';
    }

    /**
     * @param string $column_name
     * @param null $value
     * @param false $force_value
     * @return mixed
     */
    public function ValueToNiceValue(string $column_name, $value = null, bool $force_value = false): mixed
    {
        if($value instanceof DateTime) {
            $value = Dates::Timestamp($value, '');
        }

        if($value || $force_value) {
            return $value;
        }

        if($this->$column_name instanceof DateTime) {
            return Dates::Timestamp($this->$column_name, '');
        }

        return $this->$column_name;
    }

    /**
     * @param string $column_name
     * @return bool
     */
    public static function IgnoreColumn(string $column_name): bool
    {
        return in_array($column_name, ['id', 'created_at', 'created_by_id', 'edited_at', 'edited_by_id']);
    }

    /**
     * @param bool $return_query
     * @return SQL_Query|QueryExecuteResult|null
     * @throws Exception
     */
    public function Insert(bool $return_query = false): SQL_Query|QueryExecuteResult|null
    {
        return $this->_Insert($return_query);
    }

    /**
     * @param bool $return_query
     * @return SQL_Query|QueryExecuteResult|null
     * @throws Exception
     */
    public function Update(bool $return_query = false): SQL_Query|QueryExecuteResult|null
    {
        return $this->_Update($return_query);
    }

    /**
     * @param $search
     * @param ICurrentUser $user
     * @return array
     */
    public static function Suggest($search, ICurrentUser $user): array
    {
        Exception(['error' => 'Suggest not implemented', 'search' => $search, 'user' => $user]);
    }

    /**
     * @param $database
     * @param $table_name
     * @return MSSQL_TableColumn[]
     */
    public static function _GetTableColumns($database, $table_name): array
    {
        $sql = '
			SELECT
				*
			FROM
				[' . $database . '].INFORMATION_SCHEMA.COLUMNS
			WHERE
				TABLE_NAME=@
		';
        $res = static::Query($sql, [$table_name]);
        /* @var MSSQL_TableColumn[] $list */
        $list = [];
        foreach ($res['data'] as $row) {
            $t = new MSSQL_TableColumn();
            $t->FromRow($row);
            $list[] = $t;
        }
        return $list;
    }

    /**
     * @param $database
     * @return array
     */
    public static function _GetTables($database): array
    {
        $sql = 'SELECT * FROM [' . $database . '].information_schema.tables WHERE "TABLE_TYPE" <> \'VIEW\' ORDER BY "TABLE_NAME"';
        $res = static::Query($sql);
        $list = [];
        if ($res['error']) {
            return [];
        }
        if (!sizeof($res['data'])) {
            return [];
        }
        foreach ($res['data'] as $row) {
            $t = $row['TABLE_NAME'];
            if (str_starts_with($t, 'TEMP')) {
                continue;
            }

            $list[] = $t;
        }
        return $list;
    }

    /**
     * @param array|null $exclude
     * @return array
     */
    public static function _GetDatabases(?array $exclude = null): array
    {
        $sql = '
          SELECT name FROM sys.databases
        ';
        $res = static::Query($sql);
        if ($res['error']) {
            Exception($res);
        }
        $list = [];
        foreach ($res['data'] as $row) {
            if (stristr($row['name'], '$') !== false) {
                continue;
            }
            if ($exclude) {
                foreach ($exclude as $ex) {
                    if (strcasecmp($row['name'], $ex) == 0) {
                        continue 2;
                    }
                }
            }
            $list[] = $row['name'];
        }
        return $list;
    }

    /**
     * @return array
     */
    public static function GetTables(): array
    {
        static::_connect();
        return static::$connection->GetTables();
    }

    /**
     * @return array
     */
    public static function GetDatabases(): array
    {
        static::_connect();
        return static::$connection->GetDatabases();
    }

    /**
     * @param string $db_base
     * @return void
     */
    public static function SetDatabase(string $db_base): void
    {
        static::_connect();

        static::$connection->SetDatabase($db_base);
    }

    /**
     * @param string $table_name
     * @return array
     */
    public static function GetTableColumns(string $table_name): array
    {
        static::_connect();

        return static::$connection->GetTableColumns($table_name);
    }

    /**
     * @param string $table_name
     * @return array
     */
    public static function GetTableIndexes(string $table_name): array
    {
        static::_connect();

        return static::$connection->GetTableIndexes($table_name);
    }

    /**
     * @param string $table_name
     * @return mixed
     */
    public static function GetUniqueKeys(string $table_name): mixed
    {
        static::_connect();

        return static::$connection->GetUniqueKeys($table_name);
    }

    /**
     * @param string $table_name
     * @return mixed
     */
    public static function GetIndexes(string $table_name): mixed
    {
        static::_connect();

        return static::$connection->GetIndexes($table_name);
    }

    /**
     * @param string $table_name
     * @return mixed
     */
    public static function GetForeignKeys(string $table_name): mixed
    {
        static::_connect();

        return static::$connection->GetForeignKeys($table_name);
    }

    /**
     * @param string $table_name
     * @return mixed
     */
    public static function GetLinkedTables(string $table_name): mixed
    {
        static::_connect();

        return static::$connection->GetLinkedTables($table_name);
    }

    /**
     * @return MSSQL_Trigger[]
     */
    public static function GetTriggers(): array
    {
        static::_connect();

        return static::$connection->GetTriggers();
    }

    /**
     * @return MSSQL_StoredProc[]
     */
    public static function GetStoredProcs(): array
    {
        static::_connect();

        return static::$connection->GetStoredProcs();
    }

    /**
     * @return MSSQL_Definition[]
     */
    public static function GetDefinitions(): array
    {
        static::_connect();

        return static::$connection->GetDefinitions();
    }

    /**
     * @param $stored_proc
     * @return MSSQL_StoredProcParam[]
     */
    public static function GetStoredProcParams($stored_proc): array
    {
        static::_connect();

        return static::$connection->GetStoredProcParams($stored_proc);
    }


    /**
     * @param string $table_name
     * @return array
     */
    public static function GetPrimaryKey(string $table_name): array
    {
        static::_connect();

        return static::$connection->GetPrimaryKey($table_name);
    }

    /**
     * @param string $sql
     * @param array|null $params
     * @param bool $large
     * @return QueryExecuteResult|null
     */
    public static function Execute(string $sql, ?array $params = null, bool $large = false): ?QueryExecuteResult
    {
        static::_connect();

        if (isset(static::$database)) {
            static::$connection->SetDatabase(static::$database);
        }

        try {
            return static::$connection->Execute($sql, $params, $large);
        } catch (Exception $ex) {
            Exception($ex);
        }
    }

    /**
     * @param string $sql
     * @param array|null $params
     * @param callable $map_function
     * @return mixed
     */
    public static function QueryMap(string $sql, ?array $params, callable $map_function): mixed
    {
        static::_connect();

        if (isset(static::$database)) {
            static::$connection->SetDatabase(static::$database);
        }

        return static::$connection->Query($sql, $params, $map_function);
    }

    /**
     * @param string $sql
     * @param array|null $params
     * @param bool $objects_only
     * @param callable|null $map_function
     * @return array
     */
    public static function Query(string $sql, ?array $params = null, bool $objects_only = false, ?callable $map_function = null): array
    {
        static::_connect();

        if ($objects_only) {
            $return_type = get_called_class();
            $map_function = function ($row) use ($return_type) {
                $c = new $return_type();
                $c->FromRow($row);
                return $c;
            };
        }

        if (isset(static::$database)) {
            static::$connection->SetDatabase(static::$database);
        }

        return static::$connection->Query($sql, $params, $map_function);
    }

    /**
     * @return string
     */
    public static function GUID(): string
    {
        $sql = 'SELECT UPPER(SUBSTRING(master.dbo.fn_varbintohexstr(HASHBYTES(\'MD5\',cast(NEWID() as varchar(36)))), 3, 32)) AS guid';

        static::_connect();

        if (isset(static::$database))
            static::$connection->SetDatabase(static::$database);

        $res = static::$connection->Query($sql);
        if ($res['error']) {
            Exception($res);
        }
        return $res['data'][0]['guid'];
    }

    /**
     * @return ISQLConnection|null
     */
    public static function getConnection(): ?ISQLConnection
    {
        return static::$connection;
    }

    /**
     * @param int|null $LastID
     * @return int|string|null
     */
    public static function LastID(?int $LastID = null): int|string|null
    {
        static::_connect();

        return static::$connection->LastID($LastID);
    }


    /**
     * @return QueryExecuteResult
     */
    public function Remove(): QueryExecuteResult
    {
        if (!$this->CanDelete()) {
            return new QueryExecuteResult();
        }

        if ($this->UsesChangeLog()) {
            $uuid = $this->PrimaryKey();

            if ($uuid) {
                ChangeLog::Delete($this);
            }
        }


        // rows are removed based on the columns which
        // make the row unique
        $where = [];
        if (sizeof(static::$_primary) > 0) {
            foreach (static::$_primary as $column)
                $where[] = $column . ' = ' . MSSQL::EscapeString($this->{$column});
        } elseif (sizeof(static::$_unique) > 0) {
            foreach (static::$_unique as $column)
                $where[] = $column . ' = ' . MSSQL::EscapeString($this->{$column});
        } else {
            $q = new QueryExecuteResult();
            $q->error = 'unique or primary key required';
            return $q;
        }

        $sql = '
			DELETE FROM
				[' . static::$database . '].dbo.[' . static::$table . ']
			WHERE
				' . implode(' AND ', $where) . '
		';
        $res = self::Execute($sql);

        if (method_exists($this, 'ElasticRemove')) {
            $this->ElasticRemove();
        }

        return $res;
    }

    /**
     * @param $col
     * @param $val
     *
     * @return array
     */
    #[ArrayShape(['col' => 'string', 'params' => 'array'])]
    protected static function _parse_col_val($col, $val): array
    {
        // extra + symbols allow us to do AND on the same column
        $col = str_replace('+', '', $col);
        $col = '[' . $col . ']';

        // helper to mint a new named parameter and collect into $params
        static $paramCounter = 0;
        $params = [];
        $newParam = function($value) use (&$params, &$paramCounter): string {
            $name = '@param' . (++$paramCounter);
            $params[$name] = $value;
            return $name;
        };

        if (is_null($val)) {
            $col = $col . ' IS NULL ';
            return ['col' => $col, 'params' => $params];
        }

        if (is_object($val)) {
            if ($val instanceof DateTime) {
                $val = $val->format('Y-m-d H:i:s');
            } else {
                Exception(['QuickDRY Error' => '$val is object', $val]);
            }
        }

        $val = (string)$val;

        if (str_starts_with($val, '{BETWEEN} ')) {
            $val = trim(Strings::RemoveFromStart('{BETWEEN}', $val));
            $parts = array_map('trim', explode(',', $val, 2));
            $p1 = $newParam($parts[0] ?? null);
            $p2 = $newParam($parts[1] ?? null);
            $col = $col . " BETWEEN $p1 AND $p2";

        } elseif (str_starts_with($val, '{IN} ')) {
            $val = trim(Strings::RemoveFromStart('{IN}', $val));
            $list = array_map('trim', explode(',', $val));
            $ph  = [];
            foreach ($list as $v) { $ph[] = $newParam($v); }
            $col = $col . ' IN (' . implode(', ', $ph) . ')';

        } elseif (str_starts_with($val, '{DATE} ')) {
            $v = trim(Strings::RemoveFromStart('{DATE}', $val));
            $p = $newParam($v);
            $col = 'CONVERT(date, ' . $col . ") = $p";

        } elseif (str_starts_with($val, '{YEAR} ')) {
            $v = trim(Strings::RemoveFromStart('{YEAR}', $val));
            $p = $newParam($v);
            $col = 'DATEPART(yyyy, ' . $col . ") = $p";

        } elseif (str_starts_with($val, '{NLIKE} ')) {
            $v = trim(Strings::RemoveFromStart('{NLIKE}', $val));
            $p = $newParam($v);
            $col = $col . " NOT LIKE $p";

        } elseif (str_starts_with($val, '{NILIKE} ')) {
            $v = trim(Strings::RemoveFromStart('{NILIKE}', $val));
            $p = $newParam($v);
            $col = 'LOWER(' . $col . ") NOT LIKE LOWER($p) ";

        } elseif (str_starts_with($val, '{ILIKE} ')) {
            // Note: SQL Server doesn’t support ILIKE; this keeps your original intention.
            $v = trim(Strings::RemoveFromStart('{ILIKE}', $val));
            $p = $newParam($v);
            $col = 'LOWER(' . $col . ") LIKE LOWER($p) ";

        } elseif (str_starts_with($val, '{NOT IN} ')) {
            $list = array_map('trim', explode(',', trim(Strings::RemoveFromStart('{NOT IN} ', $val))));
            $hasNull = false;
            $ph = [];
            foreach ($list as $v) {
                if (strtolower($v) === 'null') { $hasNull = true; continue; }
                $ph[] = $newParam($v);
            }
            if ($hasNull) {
                // (col IS NOT NULL OR col NOT IN (...))
                $col = '(' . $col . ' IS NOT NULL' . (count($ph) ? ' OR ' . $col . ' NOT IN (' . implode(', ', $ph) . ')' : '') . ')';
            } else {
                $col = $col . ' NOT IN (' . implode(', ', $ph) . ')';
            }

        } elseif (str_starts_with($val, '{LIKE} ')) {
            $v = trim(Strings::RemoveFromStart('{LIKE}', $val));
            $p = $newParam($v);
            $col = $col . " LIKE $p";

        } elseif (str_starts_with($val, '<= ')) {
            $v = trim(Strings::RemoveFromStart('<=', $val));
            $p = $newParam($v);
            $col = $col . " <= $p ";

        } elseif (str_starts_with($val, '>= ')) {
            $v = trim(Strings::RemoveFromStart('>=', $val));
            $p = $newParam($v);
            $col = $col . " >= $p ";

        } elseif (str_starts_with($val, '<> ')) {
            $v = trim(Strings::RemoveFromStart('<>', $val));
            if (strtolower($v) !== 'null') {
                $p = $newParam($v);
                $col = $col . " <> $p ";
            } else {
                $col = $col . ' IS NOT NULL';
            }

        } elseif (str_starts_with($val, '< ')) {
            $v = trim(Strings::RemoveFromStart('<', $val));
            $p = $newParam($v);
            $col = $col . " < $p ";

        } elseif (str_starts_with($val, '> ')) {
            $v = trim(Strings::RemoveFromStart('>', $val));
            $p = $newParam($v);
            $col = $col . " > $p ";

        } else {
            $p = $newParam($val);
            $col = $col . " = $p ";
        }

        return ['col' => $col, 'params' => $params];
    }


    /**
     * @param array|null $where
     * @return mixed
     */
    protected static function _Get(?array $where = null): mixed
    {
        $params = [];
        $where_sql = '1=1';
        if(is_array($where)) {
            $t = [];
            foreach ($where as $c => $v) {
                $cv = self::_parse_col_val($c, $v);
                foreach ($cv['params'] as $pk => $pv) {
                    $params[str_replace('@', '', $pk)] = $pv;
                }

                if($v && is_object($v)) {
                    if($v instanceof DateTime) {
                        $v = Dates::Timestamp($v);
                    }
                }

                if ($v && !is_array($v) && strtolower((string)$v) === 'null') {
                    $t[] = $c . ' IS NULL';
                } else {
                    $t[] = $cv['col'];
                }
            }
            $where_sql = implode(' AND ', $t);
        }

        $type = get_called_class();

        $sql = '
			SELECT
				*
			FROM
				[' . static::$database . '].dbo.[' . static::$table . ']
			WHERE
				' . $where_sql . '
			';


        $log = null;
        if (self::$UseLog) {
            $log = new SQL_Log();
            $log->source = $type;
            $log->start_time = microtime(true);
            $log->query = $sql;
            $log->params = $params;
        }

        $res = static::Query($sql, $params);

        if (self::$UseLog) {
            $log->end_time = microtime(true);
            $log->duration = $log->end_time - $log->start_time;
            self::$Log[] = $log;
        }

        if ($res['error']) {
            Exception($res);
        }

        if (isset($res['data'])) {
            foreach ($res['data'] as $r) {
                $t = new $type();
                $t->FromRow($r);

                return $t;
            }
        }
        return null;
    }

    /**
     * @param array|null $where
     * @param array|null $order_by
     * @param int|null $limit
     *
     * @return array
     */
    protected static function _GetAll(?array $where = null, ?array $order_by = null, ?int $limit = null): array
    {
        $params = [];

        $sql_order = '';
        if (is_array($order_by)) {
            $sql_order = [];
            foreach ($order_by as $col => $dir) {
                $sql_order[] .= '[' . trim($col) . ']' . ' ' . $dir;
            }
            $sql_order = 'ORDER BY ' . implode(', ', $sql_order);
        }


        $sql_where = '1=1';
        if (is_array($where)) {
            $t = [];
            foreach ($where as $c => $v) {
                $cv = self::_parse_col_val($c, $v);
                foreach ($cv['params'] as $pk => $pv) {
                    $params[str_replace('@', '', $pk)] = $pv;
                }

                if ($v && !is_array($v) && strtolower((string)$v) === 'null') {
                    $t[] = '[' . $c . '] IS NULL';
                } else {
                    $t[] = $cv['col'];
                }
            }
            $sql_where = implode(' AND ', $t);
        }

        $sql = '
			SELECT
			' . ($limit ? 'TOP ' . $limit : '') . '
				*
			FROM
				[' . static::$database . '].dbo.[' . static::$table . '] WITH (NOLOCK)
			WHERE
				' . $sql_where . '
				' . $sql_order . '
		';

        $log = null;
        if (self::$UseLog) {
            $log = new SQL_Log();
            $log->source = get_called_class();
            $log->start_time = microtime(true);
            $log->query = $sql;
            $log->params = $params;
        }

        $res = static::Query($sql, $params, true);

        if (isset($res['error'])) {
            Exception($res);
        }

        if (self::$UseLog) {
            $log->end_time = microtime(true);
            $log->duration = $log->end_time - $log->start_time;
            self::$Log[] = $log;
        }

        return $res;
    }

    /**
     * @param array|null $where
     * @return int
     */
    protected static function _GetCount(?array $where = null): int
    {
        $params = [];
        $sql_where = '1=1';
        if (is_array($where)) {
            $t = [];
            foreach ($where as $c => $v) {
                $cv = self::_parse_col_val($c, $v);
                foreach ($cv['params'] as $pk => $pv) {
                    $params[str_replace('@', '', $pk)] = $pv;
                }

                if ($v && !is_array($v) && strtolower($v) === 'null') {
                    $t[] = $c . ' IS NULL';
                } else {
                    $t[] = $cv['col'];
                }
            }
            $sql_where = implode(' AND ', $t);
        }

        $sql = '
			SELECT
				COUNT(*) AS cnt
			FROM
				[' . static::$database . '].dbo.[' . static::$table . '] WITH (NOLOCK)
			WHERE
				' . $sql_where . '
		';

        $log = null;
        if (self::$UseLog) {
            $log = new SQL_Log();
            $log->source = get_called_class();
            $log->start_time = microtime(true);
            $log->query = $sql;
            $log->params = $params;
        }

        $res = static::Query($sql, $params);

        if (self::$UseLog) {
            $log->end_time = microtime(true);
            $log->duration = $log->end_time - $log->start_time;
            self::$Log[] = $log;
        }


        if ($res['error']) {
            Exception($res);
        }

        foreach ($res['data'] as $r) {
            return $r['cnt'];
        }
        return 0;
    }

    /**
     * @param array|null $where
     * @param array|null $order_by
     * @param int $page
     * @param int $per_page
     * @param array|null $left_join
     * @param int $limit
     *
     * @return array
     */
    #[ArrayShape(['count' => 'int|mixed', 'items' => 'array', 'sql' => 'string'])] protected static function _GetAllPaginated(
        ?array $where = null,
        ?array $order_by = null,
        int    $page = 0,
        int    $per_page = 0,
        ?array $left_join = null,
        int    $limit = 0): array
    {
        $type = get_called_class();

        $params = [];

        $sql_order = [];
        if (is_array($order_by) && sizeof($order_by)) {
            foreach ($order_by as $col => $dir) {
                $sql_order[] .= '[' . trim($col) . '] ' . $dir;
            }
            $sql_order = 'ORDER BY ' . implode(', ', $sql_order);
        } else {
            $sql_order = '';
        }

        if (!$sql_order) {
            $primary = isset(static::$_primary) ? static::$_primary[0] : 'id';
            $dir = 'asc';
            $sql_order = ' ORDER BY ' . $primary . ' ' . $dir;
        }

        $sql_where = '1=1';
        if (is_array($where)) {
            $t = [];
            foreach ($where as $c => $v) {
                $cv = self::_parse_col_val($c, $v);
                foreach ($cv['params'] as $pk => $pv) {
                    $params[str_replace('@', '', $pk)] = $pv;
                }

                if ($v && !is_array($v) && strtolower($v) === 'null') {
                    $t[] = $c . ' IS NULL';
                } else {
                    $t[] = $cv['col'];
                }
            }
            $sql_where = implode(' AND ', $t);
        }

        $sql_left = '';
        if (is_array($left_join)) {
            foreach ($left_join as $join) {
                $sql_left .= 'LEFT JOIN  [' . $join['database'] . '].dbo.[' . $join['table'] . '] AS ' . $join['as'] . ' WITH (NOLOCK) ON ' . $join['on'] . "\r\n";
            }
        }


        if (!$limit) {
            $sql = '
SELECT
    COUNT(*) AS num
FROM
    [' . static::$database . '].dbo.[' . static::$table . '] WITH (NOLOCK)
    ' . $sql_left . '
WHERE
    ' . $sql_where . '
';
        } else {
            $sql = '
SELECT 
  COUNT(*) AS num 
FROM (
    SELECT TOP ' . $limit . ' * FROM [' . static::$database . '].dbo.[' . static::$table . '] WITH (NOLOCK)
        ' . $sql_left . '
    WHERE
        ' . $sql_where . '
) AS c
			';
        }

        $res = static::Query($sql, $params);
        if ($res['error']) {
            Exception($res);
        }

        $count = $res['data'][0]['num'] ?? 0;
        $list = [];
        if ($count > 0) {
            $sql = '
SELECT
    [' . static::$table . '].*
FROM
    [' . static::$database . '].dbo.[' . static::$table . '] WITH (NOLOCK)
    ' . $sql_left . '
WHERE
     ' . $sql_where . '
' . $sql_order . '
			';
            if ($per_page != 0) {
                $sql .= '
OFFSET ' . ($per_page * $page) . ' ROWS FETCH NEXT ' . $per_page . ' ROWS ONLY
				';
            }

            $res = static::Query($sql, $params);

            if ($res['error']) {
                Exception($res);
            }

            foreach ($res['data'] as $r) {
                $t = new $type();
                $t->FromRow($r);
                $list[] = $t;
            }
        }
        return ['count' => $count, 'items' => $list, 'sql' => $sql];
    }

    /**
     * Base SQL Server type of a column, lower case and without any length,
     * e.g. "decimal", "nvarchar", "datetime2". The code generator stores
     * INFORMATION_SCHEMA.COLUMNS.DATA_TYPE, which never has a length, but
     * hand-written classes sometimes say "decimal(18,2)" or "varchar(max)".
     *
     * @param string $name
     * @return string
     */
    protected static function ColumnBaseType(string $name): string
    {
        $type = strtolower(trim((string)static::$prop_definitions[$name]['type']));
        return (string)preg_replace('/[\s(].*$/s', '', $type);
    }

    /**
     * Scale (digits after the decimal point) of a decimal / numeric column,
     * from the 'length' the code generator stored ("precision,scale"), or
     * from the type itself when it was written as "decimal(18,2)".
     *
     * Classes generated before the code generator was fixed stored
     * "precision,radix" here, so their scale reads as 10. That only adds
     * trailing zeros; SQL Server still rounds to the real scale on save.
     *
     * @param string $name
     * @return int|null null when the scale isn't known
     */
    protected static function DecimalScale(string $name): ?int
    {
        $type = (string)static::$prop_definitions[$name]['type'];
        if (preg_match('/\(\s*\d+\s*,\s*(\d+)\s*\)/', $type, $m)) {
            return (int)$m[1];
        }
        $length = (string)(static::$prop_definitions[$name]['length'] ?? '');
        if (preg_match('/^\s*\d+\s*,\s*(\d+)\s*$/', $length, $m)) {
            return (int)$m[1];
        }
        return null;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    protected static function IsNumeric($name): bool
    {
        return in_array(static::ColumnBaseType($name), [
            'bit', 'tinyint', 'smallint', 'int', 'bigint',
            'decimal', 'numeric', 'money', 'smallmoney',
            'float', 'real',
        ], true);
    }

    /**
     * Rounds a plain decimal string ("-123.4567") to $scale places, half
     * away from zero like SQL Server does, using string math so large
     * decimal values don't lose precision through a float.
     *
     * @param string $number
     * @param int $scale
     * @return string
     */
    protected static function RoundDecimalString(string $number, int $scale): string
    {
        $negative = str_starts_with($number, '-');
        $number = ltrim($number, '+-');
        [$int, $frac] = array_pad(explode('.', $number, 2), 2, '');
        $int = ltrim($int, '0');
        if ($int === '') {
            $int = '0';
        }

        if (strlen($frac) > $scale) {
            $round_up = $frac[$scale] >= '5';
            $frac = substr($frac, 0, $scale);
            if ($round_up) {
                $digits = $int . $frac;
                $i = strlen($digits) - 1;
                while ($i >= 0 && $digits[$i] === '9') {
                    $digits[$i] = '0';
                    $i--;
                }
                if ($i < 0) {
                    $digits = '1' . $digits;
                } else {
                    $digits[$i] = chr(ord($digits[$i]) + 1);
                }
                $int = $scale ? substr($digits, 0, -$scale) : $digits;
                $frac = $scale ? substr($digits, -$scale) : '';
            }
        } else {
            $frac = str_pad($frac, $scale, '0');
        }

        $result = $int . ($scale > 0 ? '.' . $frac : '');
        if ($negative && trim($result, '0.') !== '') {
            $result = '-' . $result;
        }
        return $result;
    }

    /**
     * Turns any value given for a numeric column into a plain number string
     * ("1234.5", "-3"), or throws if it isn't a number.
     *
     * @param string $name
     * @param mixed $value
     * @param string $type
     * @return string
     * @throws Exception
     */
    protected static function NumericString(string $name, mixed $value, string $type): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value)) {
            return (string)$value;
        }
        if (is_float($value)) {
            if (!is_finite($value)) {
                throw new Exception($name . ' (' . $type . ') cannot be ' . $value);
            }
            $value = (string)$value; // may be "1.0E+25", handled below
        }

        $s = trim((string)$value);
        // a clean number that isn't in scientific notation is used as is,
        // so big decimals never go through a float
        if (is_numeric($s) && stripos($s, 'e') === false) {
            return ltrim($s, '+');
        }

        // "$1,234.50", "1e5", "12 USD" ...
        $n = (string)Strings::Numeric($s);
        if (!is_numeric($n)) {
            throw new Exception($name . ' (' . $type . ') must be a number, got "' . $s . '"');
        }
        return ltrim($n, '+');
    }

    /**
     * Formats a date for a parameter in an INSERT / UPDATE. datetime2 keeps
     * its fraction of a second and datetimeoffset keeps its UTC offset;
     * without the offset SQL Server would store the time as +00:00.
     *
     * @param string $name
     * @param DateTimeInterface $date
     * @return string
     */
    protected static function SqlDateString(string $name, DateTimeInterface $date): string
    {
        return match (static::ColumnBaseType($name)) {
            'datetime2'      => $date->format('Y-m-d\TH:i:s.u'),
            'datetimeoffset' => $date->format('Y-m-d\TH:i:s.uP'),
            default          => $date->format('Y-m-d H:i:s'),
        };
    }

    /**
     * Converts a value to the PHP type that matches the column's SQL Server
     * type, and throws an Exception when the value can't be stored in that
     * column.
     *
     * Covers every SQL Server data type through SQL Server 2025:
     *  - bit                                      → int 0 / 1
     *  - tinyint, smallint, int, bigint            → int (range checked)
     *  - decimal                                   → string rounded to the column scale
     *  - numeric, money, smallmoney, float, real   → float
     *  - char, varchar, nchar, nvarchar, text,
     *    ntext, sysname, xml, json                 → string
     *  - uniqueidentifier                          → string (format checked)
     *  - date, datetime, datetime2,
     *    smalldatetime, datetimeoffset             → DateTime
     *  - time                                      → string (it's a time of day, not a date)
     *  - binary, varbinary, image, timestamp,
     *    rowversion                                → raw byte string
     *  - geometry, geography, hierarchyid, vector  → string as the driver returns it
     *  - sql_variant                               → unchanged
     *
     * Empty values: '' / null in a nullable column is null. In a NOT NULL
     * numeric column it is 0, as before.
     *
     * @param string $name
     * @param $value
     * @param bool $just_checking when true, the literal 'null' in a NOT NULL column doesn't throw
     * @return mixed
     * @throws Exception
     */
    protected static function StrongType(string $name, $value, bool $just_checking = false): mixed
    {
        if ($value === '#NULL!') { // Excel files may have this
            $value = null;
        }

        if (is_array($value)) {
            return null;
        }

        // we can always set primary keys to null
        if (is_null($value) && in_array($name, static::$_primary)) {
            return null;
        }

        $type = static::ColumnBaseType($name);
        $nullable = (bool)static::$prop_definitions[$name]['is_nullable'];

        $date_types = ['date', 'datetime', 'datetime2', 'smalldatetime', 'datetimeoffset'];

        if (is_object($value)) {
            if ($value instanceof DateTimeInterface) {
                if (in_array($type, $date_types, true)) {
                    return DateTime::createFromInterface($value);
                }
                $value = Dates::Timestamp($value);
            } else {
                return null;
            }
        }

        if (is_string($value) && strcasecmp(trim($value), 'null') == 0) {
            if (!$just_checking && !$nullable) {
                throw new Exception($name . ' cannot be null');
            }
            return null;
        }

        // nothing set, or a blank form field
        $empty = is_null($value) || (is_string($value) && trim($value) === '');

        switch ($type) {
            // --- integers ---
            case 'bit':
                if ($empty) {
                    return $nullable ? null : 0;
                }
                if (is_bool($value)) {
                    return $value ? 1 : 0;
                }
                $s = strtolower(trim((string)$value));
                if ($s === 'true') {
                    return 1;
                }
                if ($s === 'false') {
                    return 0;
                }
                if (!is_numeric($s)) {
                    throw new Exception($name . ' (bit) must be 0, 1, true or false, got "' . $value . '"');
                }
                return (float)$s != 0 ? 1 : 0;

            case 'tinyint':
            case 'smallint':
            case 'int':
            case 'bigint':
                if ($empty) {
                    return $nullable ? null : 0;
                }
                $n = static::NumericString($name, $value, $type);
                // drop any fraction, as before ("12.7" → 12)
                $digits = explode('.', $n, 2)[0];
                if ($digits === '' || $digits === '-') {
                    $digits .= '0';
                }
                [$min, $max] = match ($type) {
                    'tinyint'  => [0, 255],
                    'smallint' => [-32768, 32767],
                    'int'      => [-2147483648, 2147483647],
                    'bigint'   => [PHP_INT_MIN, PHP_INT_MAX],
                };
                $int = filter_var($digits, FILTER_VALIDATE_INT);
                if ($int === false || $int < $min || $int > $max) {
                    throw new Exception($name . ' (' . $type . ') must be between ' . $min . ' and ' . $max . ', got "' . $value . '"');
                }
                return $int;

            // --- exact decimal: kept as a string so no precision is lost ---
            case 'decimal':
                if ($empty) {
                    $value = $nullable ? null : 0;
                    if (is_null($value)) {
                        return null;
                    }
                }
                $n = static::NumericString($name, $value, $type);
                $scale = static::DecimalScale($name);
                if (is_null($scale)) {
                    return $n;
                }
                return static::RoundDecimalString($n, $scale);

            // --- numeric / money / floating point ---
            case 'numeric':
            case 'money':
            case 'smallmoney':
            case 'float':
            case 'real':
                if ($empty) {
                    return $nullable ? null : 0.0;
                }
                return (float)static::NumericString($name, $value, $type);

            // --- character strings ---
            case 'char':
            case 'varchar':
            case 'nchar':
            case 'nvarchar':
            case 'text':
            case 'ntext':
            case 'sysname':
            case 'xml':
            case 'json':
                return is_null($value) ? null : (string)$value;

            case 'uniqueidentifier':
                if ($empty) {
                    return null;
                }
                $guid = trim((string)$value);
                if (!preg_match('/^\{?[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}}?$/i', $guid)) {
                    throw new Exception($name . ' (uniqueidentifier) must be a GUID, got "' . $guid . '"');
                }
                return $guid;

            // --- dates and times ---
            case 'date':
            case 'datetime':
            case 'datetime2':
            case 'smalldatetime':
            case 'datetimeoffset':
                if ($empty) {
                    return null;
                }
                try {
                    return new DateTime((string)$value);
                } catch (Exception) {
                    throw new Exception($name . ' (' . $type . ') must be a date, got "' . $value . '"');
                }

            case 'time':
                if ($empty) {
                    return null;
                }
                return (string)$value;

            // --- binary (raw bytes) ---
            case 'binary':
            case 'varbinary':
            case 'image':
            case 'timestamp': // SQL Server's timestamp is rowversion, not a date
            case 'rowversion':
            // --- CLR / spatial / vector, as the driver returns them ---
            case 'geometry':
            case 'geography':
            case 'hierarchyid':
            case 'vector':
                return is_null($value) ? null : (string)$value;

            case 'sql_variant':
                return $value;

            // hand-written classes
            case 'bool':
            case 'boolean':
                return is_null($value) ? null : (bool)$value;
        }

        throw new Exception('Unknown SQL Server type ' . static::$prop_definitions[$name]['type'] . ' for column ' . $name);
    }

    /**
     * @param bool $force_insert
     * @return QueryExecuteResult
     * @throws Exception
     */
    protected function _GetSaveQuery(bool $force_insert = false): QueryExecuteResult
    {
        return $this->_Save($force_insert, true);
    }

    /**
     * @param bool $force_insert
     * @param bool $return_query
     * @return QueryExecuteResult|SQL_Query|array
     * @throws Exception
     */
    protected function _Save(bool $force_insert = false, bool $return_query = false): QueryExecuteResult|SQL_Query|array
    {
        /* @var string[] $primary */
        $primary = static::$_primary ?? [];

        $primary_set = (bool)sizeof($primary);
        $primary_sql = [];
        $params = [];
        foreach ($primary as $col) {
            if (!$this->$col) {
                $primary_set = false;
                break;
            }
            $primary_sql[] = '[' . $col . '] = ' . MSSQL::EscapeString($this->$col);
        }

        if ($primary_set) {
            foreach ($primary as $col) {
                if (is_null($this->$col))
                    $params[$col] = 'null';
                else {
                    $params[$col] = $this->$col;
                }
            }

            $type = static::class;
            if (!method_exists($type, 'Get')) {
                Exception("$type::Get");
            }
            $t = $type::Get($params);
            if (!$t) {
                $force_insert = true;
            }
        }

        $unique_set = false;

        if (!$primary_set && sizeof(static::$_unique)) { // if we have a unique key defined then check it and load the object if it exists

            foreach (static::$_unique as $cols) {
                $params = [];

                foreach ($cols as $col) {
                    if (is_null($this->$col))
                        $params[$col] = 'null';
                    else {
                        $params[$col] = $this->$col;
                        $unique_set = true;
                    }
                }
                if ($unique_set) {
                    $type = static::class;
                    $t = $type::Get($params);

                    if (!is_null($t)) {
                        $primary_set = true;
                        foreach ($cols as $col) {
                            if ($t->$col) {
                                $this->$col = $t->$col;
                                $primary_sql[] = '[' . $col . '] = ' . MSSQL::EscapeString($this->$col);
                            }
                        }
                        $vars = $t->ToArray();
                        foreach ($vars as $k => $v) {
                            if (isset($this->$k) && is_null($this->$k)) {
                                // if the current object value is null, fill it in with the existing object's info
                                $this->$k = $v;
                            }
                        }
                        break; // only find the first match with unique key definition
                    }
                }
            }
        }


        if (!$primary_set || $force_insert) {
            $sql = '
				INSERT INTO
					[' . static::$database . '].dbo.[' . static::$table . ']
				';
            $props = [];
            $params = [];
            $qs = [];
            foreach ($this->props as $name => $value) {
                if (in_array($name, $primary) && (is_null($this->$name) || !$this->$name)) {
                    continue;
                }

                $props[] = $name;

                $st_value = static::StrongType($name, $value);


                if (!is_object($value) && (is_null($st_value) || strtolower(trim((string)$value)) === 'null') && (self::IsNumeric($name) || (!self::IsNumeric($name) && !$this->PRESERVE_NULL_STRINGS))) {
                    $qs[] = 'NULL --' . $name . ' / ' . static::$prop_definitions[$name]['type'] . PHP_EOL;
                } else {
                    $qs[] = '@' . $name . ' --' . $name . ' / ' . static::$prop_definitions[$name]['type'] . PHP_EOL;
                    if ($st_value instanceof DateTime) {
                        $st_value = static::SqlDateString($name, $st_value);
                    }
                    $params[$name] = '{{{' . $st_value . '}}}'; // necessary to get past the null check in EscapeString
                }

            }
            $sql .= '(
            [' . implode('],[', $props) . ']
            ) VALUES (
            ' . implode(',', $qs) . '
            )';

            if ($primary_set && !$force_insert) {
                $sql .= '
WHERE
    ' . implode(' AND ', $primary_sql) . '
';
            }

//            Debug($sql, $params, $this);

            if ($return_query) {
                return new SQL_Query($sql, $params);
            }
            $res = static::Execute($sql, $params);
            $res->params = $params;

            if ($res->error) {
                Exception($res);
            }

            if (!$primary_set) {
                // there can only be one auto incrementing column per table
                foreach ($primary as $col) {
                    $this->$col = static::LastID();
                }
            }
        } else {
            $sql = '
UPDATE
    [' . static::$database . '].dbo.[' . static::$table . ']
SET
';
            $props = [];
            $params = [];
            foreach ($this->props as $name => $value) {
                if (!isset($this->_change_log[$name])) {
                    continue;
                }
                if (in_array($name, $primary)) {
                    continue;
                }

                $st_value = static::StrongType($name, $value);

                if (!is_object($value) && (
                    is_null($st_value)
                        || strtolower(trim((string)$value)) === 'null'
                    ) && (
                        self::IsNumeric($name)
                        || (
                            !self::IsNumeric($name)
                            && !$this->PRESERVE_NULL_STRINGS
                        )
                    )
                ) {
                    $props[] = '[' . $name . '] = NULL -- ' . $name . ' / ' . static::$prop_definitions[$name]['type'] . PHP_EOL;
                } else {
                    $props[] = '[' . $name . '] = @' . $name . ' --' . $name . ' / ' . static::$prop_definitions[$name]['type'] . PHP_EOL;

                    if ($st_value instanceof DateTime) {
                        $st_value = static::SqlDateString($name, $st_value);
                    }
                    $params[$name] = '{{{' . $st_value . '}}}'; // necessary to get past the null check in EscapeString
                }
            }
            if (!sizeof($props)) {
                return new QueryExecuteResult();
            }

            $sql .= implode(' , ', $props);

            $sql .= '
WHERE
  ' . implode(' AND ', $primary_sql) . '
';


//            Debug($sql, $params, $this->_change_log, $this);
            if ($return_query) {
                return new SQL_Query($sql, $params);
            }
            $res = static::Execute($sql, $params);
        }

        if ($this->HasChangeLog()) {
            $uuid = $this->PrimaryKey();
            if ($uuid) {
                ChangeLog::Create($this);
                $this->_change_log = [];

            }
        }
        return $res;
    }

    /**
     * @param bool $return_query
     *
     * @return QueryExecuteResult|SQL_Query|null
     * @throws Exception
     */
    protected function _Insert(bool $return_query = false): SQL_Query|QueryExecuteResult|null
    {
        $primary = static::$_primary ?? [];
        $primary_set = true;
        foreach ($primary as $col) {
            if (!$this->$col) { // null, zero, or empty string
                $primary_set = false;
            }
        }

        $sql = '
INSERT INTO
    [' . static::$database . '].dbo.[' . static::$table . ']
';
        $props = [];
        $params = [];
        $qs = [];
        foreach ($this->props as $name => $value) {
            if (in_array($name, $primary) && !$this->$name) { // null or zero or empty string
                continue;
            }

            $props[] = $name;

            $st_value = static::StrongType($name, $value);


            if (!is_object($value) && (is_null($st_value)
                    || strtolower(trim((string)$value)) === 'null')
                && (self::IsNumeric($name) || (!self::IsNumeric($name) && !$this->PRESERVE_NULL_STRINGS))) {
                $qs[] = 'NULL --' . $name . PHP_EOL;
            } else {

                if ($st_value instanceof DateTime) {
                    $st_value = static::SqlDateString($name, $st_value);
                }

                $qs[] = '@' . $name . ' --' . $name . PHP_EOL;
                $params[$name] = '{{{' . $st_value . '}}}'; // necessary to get past the null check in EscapeString
            }

        }
        $sql .= '([' . implode('],[', $props) . ']) VALUES (' . implode(',', $qs) . ')';


        if ($return_query) {
            return new SQL_Query($sql, $params);
        }
        $res = static::Execute($sql, $params);

        if (!$primary_set) {
            // there can only be one auto incrementing column per table
            foreach ($primary as $col) {
                if (is_null($this->$col)) {
                    $this->$col = static::LastID();
                    break;
                }
            }
        }

        return $res;
    }

    /**
     * @param bool $return_query
     * @return QueryExecuteResult|SQL_Query|null
     * @throws Exception
     */
    protected function _Update(bool $return_query = false): SQL_Query|QueryExecuteResult|null
    {
        if (!sizeof($this->_change_log)) {
            return null;
        }

        $primary = static::$_primary ?? [];

        $primary_set = false;
        $primary_sql = [];
        foreach ($primary as $col) {
            $primary_set = true;
            if (!$this->$col) {
                $primary_set = false;
                break;
            }
            $primary_sql[] = '[' . $col . '] = ' . MSSQL::EscapeString($this->$col);
        }
        if (!$primary_set && isset(static::$_unique[0])) {
            foreach (static::$_unique[0] as $col) {
                $primary_set = true;
                if (!$this->$col) {
                    $primary_set = false;
                    break;
                }
                $primary_sql[] = '[' . $col . '] = ' . MSSQL::EscapeString($this->$col);
            }
        }

        $sql = '
UPDATE
    [' . static::$database . '].dbo.[' . static::$table . ']
SET
';
        $props = [];
        $params = [];
        foreach ($this->props as $name => $value) {
            if (!isset($this->_change_log[$name])) {
                continue;
            }
            if (in_array($name, $primary)) {
                continue;
            }

            $st_value = static::StrongType($name, $value);


            if (!is_object($value)
                && (is_null($st_value) || strtolower(trim((string)$value)) === 'null')
                && (self::IsNumeric($name) || (!self::IsNumeric($name) && !$this->PRESERVE_NULL_STRINGS))
            ) {
                $props[] = '[' . $name . '] = NULL -- ' . $name . PHP_EOL;
            } else {
                $props[] = '[' . $name . '] = @' . $name . ' --' . $name . PHP_EOL;

                if ($st_value instanceof DateTime) {
                    $st_value = static::SqlDateString($name, $st_value);
                }

                $params[$name] = '{{{' . $st_value . '}}}'; // necessary to get past the null check in EscapeString
            }
        }
        $sql .= implode(',', $props);

        if ($primary_set) {
            $sql .= '
WHERE
    ' . implode(' AND ', $primary_sql) . '
';
        }

        if ($return_query) {
            return new SQL_Query($sql, $params);
        }


        return static::Execute($sql, $params);
    }

    /**
     * @return bool
     */
    public function CanDelete(): bool
    {
        return false;
    }
}