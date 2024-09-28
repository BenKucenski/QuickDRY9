<?php

namespace Bkucenski\Quickdry\Connectors\mssql;

use DateTime;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use Bkucenski\Quickdry\Connectors\mssql;
use Bkucenski\Quickdry\Connectors\QueryExecuteResult;
use Bkucenski\Quickdry\Connectors\SQL_Base;
use Bkucenski\Quickdry\Connectors\SQL_Log;
use Bkucenski\Quickdry\Connectors\SQL_Query;
use Bkucenski\Quickdry\Utilities\Dates;
use Bkucenski\Quickdry\Utilities\Strings;

/**
 *
 */
class MSSQL_Core extends SQL_Base
{
    protected static array $prop_definitions = [];
    protected static array $_primary = [];
    protected static array $_unique = [];

    protected static string $DatabaseTypePrefix = 'ms';
    protected static string $DatabasePrefix = '';
    protected static int $LowerCaseTable = 0;
    protected static string $DB_HOST;
    protected bool $PRESERVE_NULL_STRINGS = false;  // when true, if a property is set to the string 'null' it will be inserted as 'null' rather than null

    protected static ?MSSQL_Connection $connection = null;

    /**
     * @param bool $val
     */
    public static function SetIgnoreDuplicateError(bool $val): void
    {
        static::_connect();
        static::$connection->IgnoreDuplicateError = $val;
    }

    /**
     * @return string|null
     */
    public static function _Table(): ?string
    {
        return static::$table;
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
     * @param $exclude
     * @return array
     */
    public static function _GetDatabases($exclude = null): array
    {
        $sql = '
          SELECT name FROM sys.databases
        ';
        $res = static::Query($sql);
        if ($res['error']) {
            Debug($res);
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
     * @return mixed
     */
    public static function GetDatabases(): mixed
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
     * @return mixed
     */
    public static function GetTableColumns(string $table_name): mixed
    {
        static::_connect();

        return static::$connection->GetTableColumns($table_name);
    }

    /**
     * @param string $table_name
     * @return mixed
     */
    public static function GetTableIndexes(string $table_name): mixed
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
     * @return mixed
     */
    public static function GetPrimaryKey(string $table_name): mixed
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
    public static function Execute(string &$sql, array $params = null, bool $large = false): ?QueryExecuteResult
    {
        static::_connect();

        if (isset(static::$database)) {
            static::$connection->SetDatabase(static::$database);
        }

        try {
            return static::$connection->Execute($sql, $params, $large);
        } catch (Exception $ex) {
            Debug($ex);
        }
        return null;
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
    public static function Query(string $sql, array $params = null, bool $objects_only = false, callable $map_function = null): array
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
     * @return mixed
     */
    public static function GUID(): mixed
    {
        $sql = 'SELECT UPPER(SUBSTRING(master.dbo.fn_varbintohexstr(HASHBYTES(\'MD5\',cast(NEWID() as varchar(36)))), 3, 32)) AS guid';

        static::_connect();

        if (isset(static::$database))
            static::$connection->SetDatabase(static::$database);

        $res = static::$connection->Query($sql);
        if ($res['error']) {
            Debug($res);
        }
        return $res['data'][0]['guid'];
    }

    /**
     * @return int|string
     */
    public static function LastID(): int|string
    {
        static::_connect();

        return static::$connection->LastID();
    }

//
//    /**
//     * @param QuickDRYUser|null $User
//     * @return array|null
//     */
//    public function Remove(?QuickDRYUser $User): ?array
//    {
//        if (!$this->CanDelete($User)) {
//            return ['error' => 'No Permission'];
//        }
//
//        // if this instance wasn't loaded from the database
//        // don't try to remove it
//        if (!$this->_from_db) {
//            return ['error' => 'Invalid Request'];
//        }
//
//        if ($this->HasChangeLog()) {
//            $uuid = $this->GetUUID();
//
//            if ($uuid) {
//                $cl = new ChangeLog();
//                $cl->host = static::$DB_HOST;
//                $cl->database = static::$database;
//                $cl->table = static::$table;
//                $cl->uuid = $uuid;
//                $cl->changes = json_encode($this->_change_log);
//                $cl->user_id = is_object($User) ? $User->GetUUID() : null;
//                $cl->created_at = Dates::Timestamp();
//                $cl->object_type = static::TableToClass(static::$DatabasePrefix, static::$table, static::$LowerCaseTable, static::$DatabaseTypePrefix);
//                $cl->is_deleted = true;
//                $cl->Save();
//            }
//        }
//
//
//        // rows are removed based on the columns which
//        // make the row unique
//        $where = [];
//        if (sizeof(static::$_primary) > 0) {
//            foreach (static::$_primary as $column)
//                $where[] = $column . ' = ' . MSSQL::EscapeString($this->{$column});
//        } else {
//            if (sizeof(static::$_unique) > 0) {
//                foreach (static::$_unique as $column)
//                    $where[] = $column . ' = ' . MSSQL::EscapeString($this->{$column});
//            } else {
//                return ['error' => 'unique or primary key required'];
//            }
//        }
//
//
//        $sql = '
//			DELETE FROM
//				[' . static::$database . '].dbo.[' . static::$table . ']
//			WHERE
//				' . implode(' AND ', $where) . '
//		';
//        $res = self::Execute($sql);
//
//        if (method_exists($this, 'ElasticRemove')) {
//            $this->ElasticRemove();
//        }
//
//        return $res;
//    }

    /**
     * @param $col
     * @param $val
     *
     * @return array
     */
    #[ArrayShape(['col' => 'string', 'val' => 'mixed'])] protected static function _parse_col_val($col, $val): array
    {
        // extra + symbols allow us to do AND on the same column
        $col = str_replace('+', '', $col);

        if (is_object($val)) {
            Debug(['QuickDRY Error' => '$val is object', $val]);
        }
        if (str_starts_with($val, '{BETWEEN} ')) {
            $val = trim(Strings::RemoveFromStart('{BETWEEN}', $val));
            $val = explode(',', $val);
            $col = $col . ' BETWEEN @ AND @';
        } elseif (str_starts_with($val, '{IN} ')) {
            $val = trim(Strings::RemoveFromStart('{IN}', $val));
            $val = explode(',', $val);
            $col = $col . ' IN (' . Strings::StringRepeatCS('@', sizeof($val)) . ')';
        } elseif (str_starts_with($val, '{DATE} ')) {
            $col = 'CONVERT(date, ' . $col . ') = @';
            $val = trim(Strings::RemoveFromStart('{DATE}', $val));
        } elseif (str_starts_with($val, '{YEAR} ')) {
            $col = 'DATEPART(yyyy, ' . $col . ') = @';
            $val = trim(Strings::RemoveFromStart('{YEAR}', $val));
        } elseif (str_starts_with($val, 'NLIKE ')) {
            $col = $col . ' NOT LIKE @';
            $val = trim(Strings::RemoveFromStart('NLIKE', $val));
        } elseif (str_starts_with($val, 'NILIKE ')) {
            $col = 'LOWER(' . $col . ')' . ' NOT LIKE LOWER(@) ';
            $val = trim(Strings::RemoveFromStart('NILIKE', $val));
        } elseif (str_starts_with($val, 'ILIKE ')) {
            $col = 'LOWER(' . $col . ')' . ' ILIKE LOWER(@) ';
            $val = trim(Strings::RemoveFromStart('ILIKE', $val));
        } elseif (str_starts_with($val, 'LIKE ')) {
            $col = $col . ' LIKE @';
            $val = trim(Strings::RemoveFromStart('LIKE', $val));
        } elseif (str_starts_with($val, '<= ')) {
            $col = $col . ' <= @ ';
            $val = trim(Strings::RemoveFromStart('<=', $val));
        } elseif (str_starts_with($val, '>= ')) {
            $col = $col . ' >= @ ';
            $val = trim(Strings::RemoveFromStart('>=', $val));
        } elseif (str_starts_with($val, '<> ')) {
            $val = trim(Strings::RemoveFromStart('<>', $val));
            if (strtolower($val) !== 'null') {
                $col = $col . ' <> @ ';
            } else {
                $col = $col . ' IS NOT NULL';
                $val = null;
            }
        } elseif (str_starts_with($val, '< ')) {
            $col = $col . ' < @ ';
            $val = trim(Strings::RemoveFromStart('<', $val));
        } elseif (str_starts_with($val, '> ')) {
            $col = $col . ' > @ ';
            $val = trim(Strings::RemoveFromStart('>', $val));
        } else {
            $col = $col . ' = @ ';
        }

        return ['col' => $col, 'val' => $val];
    }

    /**
     * @param array|null $where
     * @return mixed
     */
    protected static function _Get(array $where = null): mixed
    {
        $params = [];
        $t = [];
        foreach ($where as $c => $v) {
            $cv = self::_parse_col_val($c, $v);
            $v = $cv['val'];

            if (!is_array($v) && strtolower($v) === 'null') {
                $t[] = $c . ' IS NULL';
            } else {
                $t[] = $cv['col'];
                if (is_array($v)) {
                    foreach ($v as $a) {
                        $params[] = $a;
                    }
                } elseif (!is_null($v)) {
                    $params[] = $v;
                }

            }
        }
        $where_sql = implode(' AND ', $t);

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
            Debug($res);
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
    protected static function _GetAll(array $where = null, array $order_by = null, int $limit = null): array
    {
        $params = [];

        $sql_order = '';
        if (is_array($order_by)) {
            $sql_order = [];
            foreach ($order_by as $col => $dir) {
                $sql_order[] .= trim($col) . ' ' . $dir;
            }
            $sql_order = 'ORDER BY ' . implode(', ', $sql_order);
        }


        $sql_where = '1=1';
        if (is_array($where)) {
            $t = [];
            foreach ($where as $c => $v) {
                $cv = self::_parse_col_val($c, $v);
                $v = $cv['val'];

                if (!is_array($v) && strtolower($v) === 'null') {
                    $t[] = $c . ' IS NULL';
                } else {
                    $t[] = $cv['col'];
                    if (is_array($v)) {
                        foreach ($v as $a) {
                            $params[] = $a;
                        }
                    } elseif (!is_null($v)) {
                        $params[] = $v;
                    }
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
            Debug($res);
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
    protected static function _GetCount(array $where = null): int
    {
        $sql_where = '1=1';
        $params = [];
        if (is_array($where)) {
            $t = [];
            foreach ($where as $c => $v) {
                $cv = self::_parse_col_val($c, $v);
                $v = $cv['val'];

                if (!is_array($v) && strtolower($v) === 'null') {
                    $t[] = $c . ' IS NULL';
                } else {
                    $t[] = $cv['col'];
                    if (is_array($v)) {
                        foreach ($v as $a) {
                            $params[] = $a;
                        }
                    } elseif (!is_null($v)) {
                        $params[] = $v;
                    }
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
            Debug($res);
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
        array $where = null,
        array $order_by = null,
        int   $page = 0,
        int   $per_page = 0,
        array $left_join = null,
        int   $limit = 0): array
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
        if (is_array($where) && sizeof($where)) {
            $t = [];
            foreach ($where as $c => $v) {
                $cv = self::_parse_col_val($c, $v);
                $v = $cv['val'];

                if (!is_array($v) && strtolower($v) === 'null') {
                    $t[] = $c . ' IS NULL';
                } else {
                    $t[] = $cv['col'];
                    if (is_array($v)) {
                        foreach ($v as $a) {
                            $params[] = $a;
                        }
                    } elseif (!is_null($v)) {
                        $params[] = $v;
                    }
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
            Debug($res);
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
                Debug($res);
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
     * @param $name
     *
     * @return bool
     */
    protected static function IsNumeric($name): bool
    {
        return match (static::$prop_definitions[$name]['type']) {
            'tinyint(1)', 'numeric', 'tinyint(1) unsigned', 'int(10) unsigned', 'bigint unsigned', 'decimal(18,2)', 'int(10)' => true,
            default => false,
        };
    }

    /**
     * @param string $name
     * @param $value
     * @param bool $just_checking
     * @return float|int|string|null
     */
    protected static function StrongType(string $name, $value, bool $just_checking = false): float|int|string|null
    {
        if ($value === '#NULL!') { // Excel files may have this
            $value = null;
        }

        if (is_array($value)) {
            return null;
        }

        if (is_object($value)) {
            if ($value instanceof DateTime) {
                $value = Dates::Timestamp($value);
            } else {
                return null;
            }
        }

        if (strcasecmp($value, 'null') == 0) {
            if (!$just_checking) {
                if (!static::$prop_definitions[$name]['is_nullable']) {
                    Debug($name . ' cannot be null');
                }
            }
            return null;
        }

        switch (static::$prop_definitions[$name]['type']) {
            case 'date':
                return $value ? Dates::Datestamp($value) : null;

            case 'int':
            case 'float':
            case 'decimal':
            case 'numeric':
                if (is_null($value) && static::$prop_definitions[$name]['is_nullable']) {
                    return null;
                }
                if (!is_numeric($value)) {
                    if (!$value) {
                        $value = static::$prop_definitions[$name]['is_nullable'] ? null : 0;
                    } else {
                        $value = Strings::Numeric($value);
                        if (!$value) {
                            Debug([
                                'name' => $name,
                                'value' => $value,
                                'type' => static::$prop_definitions[$name]['type'],
                                'error' => 'value must be ' . static::$prop_definitions[$name]['type'],
                            ]);
                        }
                    }
                }
                return $value;

            case 'tinyint(1)':
                return $value ? 1 : 0;

            case 'decimal(18,2)':
            case 'int(10)':
                return $value * 1.0;

            case 'timestamp':
            case 'datetime':
                return $value ? Dates::SQLDateTimeToString($value) : null;
        }
        return $value;
    }

    /**
     * @param bool $force_insert
     * @return QueryExecuteResult
     */
    protected function _GetSaveQuery(bool $force_insert = false): QueryExecuteResult
    {
        return $this->_Save($force_insert, true);
    }

    /**
     * @param bool $force_insert
     * @param bool $return_query
     * @return QueryExecuteResult|SQL_Query|array
     */
    protected function _Save(bool $force_insert = false, bool $return_query = false): QueryExecuteResult|SQL_Query|array
    {
        global $Web;

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
                Debug("$type::Get");
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


                if (!is_object($value) && (is_null($st_value) || strtolower(trim($value)) === 'null') && (self::IsNumeric($name) || (!self::IsNumeric($name) && !$this->PRESERVE_NULL_STRINGS))) {
                    $qs[] = 'NULL --' . $name . ' / ' . static::$prop_definitions[$name]['type'] . PHP_EOL;
                } else {
                    $qs[] = '@ --' . $name . ' / ' . static::$prop_definitions[$name]['type'] . PHP_EOL;
                    $params[] = '{{{' . $st_value . '}}}'; // necessary to get past the null check in EscapeString
                }

            }
            $sql .= '([' . implode('],[', $props) . ']) VALUES (' . implode(',', $qs) . ')';

            if ($primary_set && !$force_insert) {
                $sql .= '
WHERE
    ' . implode(' AND ', $primary_sql) . '
';
            }

            if ($return_query) {
                return new SQL_Query($sql, $params);
            }
            $res = static::Execute($sql, $params);

            if($res->error) {
                Debug($res);
            }

            if (!$primary_set) {
                // there can only be one auto incrementing column per table
                foreach ($primary as $col) {
                    if (is_null($this->$col)) {
                        $this->$col = static::LastID();
                        break;
                    }
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

                if (!is_object($value) && (is_null($st_value) || strtolower(trim($value)) === 'null') && (self::IsNumeric($name) || (!self::IsNumeric($name) && !$this->PRESERVE_NULL_STRINGS))) {
                    $props[] = '[' . $name . '] = NULL -- ' . $name . ' / ' . static::$prop_definitions[$name]['type'] . PHP_EOL;
                } else {
                    $props[] = '[' . $name . '] = @ --' . $name . ' / ' . static::$prop_definitions[$name]['type'] . PHP_EOL;
                    $params[] = '{{{' . $st_value . '}}}'; // necessary to get past the null check in EscapeString
                }
            }
            if (!sizeof($props)) {
                return new QueryExecuteResult();
            }

            $sql .= implode(',', $props);

            $sql .= '
WHERE
  ' . implode(' AND ', $primary_sql) . '
';

            if ($return_query) {
                return new SQL_Query($sql, $params);
            }
            $res = static::Execute($sql, $params);
        }

//        if ($this->HasChangeLog()) {
//            $uuid = $this->GetUUID();
//            if ($uuid) {
//                $cl = new ChangeLog();
//                $cl->host = static::$DB_HOST;
//                $cl->database = static::$database;
//                $cl->table = static::$table;
//                $cl->uuid = $uuid;
//                $cl->changes = json_encode($this->_change_log);
//                $cl->user_id = is_object($Web) && $Web->CurrentUser ? $Web->CurrentUser->GetUUID() : null;
//                $cl->created_at = Dates::Timestamp();
//                $cl->object_type = static::TableToClass(static::$DatabasePrefix, static::$table, static::$LowerCaseTable, static::$DatabaseTypePrefix);
//                $cl->is_deleted = false;
//                $cl->Save();
//            }
//        }
        return $res;
    }

    /**
     * @param bool $return_query
     *
     * @return QueryExecuteResult|SQL_Query|null
     */
    protected function _Insert(bool $return_query = false): SQL_Query|QueryExecuteResult|null
    {
        $primary = static::$_primary ?? [];
        $primary_set = true;
        foreach ($primary as $col) {
            if (is_null($this->$col)) {
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
            if (in_array($name, $primary) && is_null($this->$name)) {
                continue;
            }

            $props[] = $name;

            $st_value = static::StrongType($name, $value);


            if (!is_object($value) && (is_null($st_value) || strtolower(trim($value)) === 'null') && (self::IsNumeric($name) || (!self::IsNumeric($name) && !$this->PRESERVE_NULL_STRINGS))) {
                $qs[] = 'NULL --' . $name . PHP_EOL;
            } else {
                $qs[] = '@ --' . $name . PHP_EOL;
                $params[] = '{{{' . $st_value . '}}}'; // necessary to get past the null check in EscapeString
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


            if (!is_object($value) && (is_null($st_value) || strtolower(trim($value)) === 'null') && (self::IsNumeric($name) || (!self::IsNumeric($name) && !$this->PRESERVE_NULL_STRINGS))) {
                $props[] = '[' . $name . '] = NULL -- ' . $name . PHP_EOL;
            } else {
                $props[] = '[' . $name . '] = @ --' . $name . PHP_EOL;
                $params[] = '{{{' . $st_value . '}}}'; // necessary to get past the null check in EscapeString
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
}