<?php

namespace Bkucenski\Quickdry\Connectors\mysql;

use Bkucenski\Quickdry\Connectors\ChangeLog;
use Bkucenski\Quickdry\Utilities\QuickDRYUser;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use Bkucenski\Quickdry\Connectors\QueryExecuteResult;
use Bkucenski\Quickdry\Connectors\SQL_Base;
use Bkucenski\Quickdry\Connectors\SQL_Query;
use Bkucenski\Quickdry\Utilities\Dates;
use Bkucenski\Quickdry\Utilities\Strings;

/**
 * Class MySQL_Core
 */
class MySQL_Core extends SQL_Base
{
    protected static string $DB_HOST;
    protected static string $DatabasePrefix;
    protected static int $LowerCaseTable;
    protected static string $DatabaseTypePrefix;
    protected static array $_primary;
    protected static array $_unique;
    protected static array $prop_definitions;

    protected bool $PRESERVE_NULL_STRINGS = false;  // when true, if a property is set to the string 'null' it will be inserted as 'null' rather than null

    protected static ?MySQL_Connection $connection = null;

    /**
     * @return array
     */
    public static function GetTables(): array
    {
        static::_connect();

        return static::$connection->GetTables();
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
     * @return void
     */
    public static function CopyInfoSchema(): void
    {
        static::_connect();

        static::$connection->CopyInfoSchema();
    }

    /**
     * @param string $table
     * @return mixed
     */
    public static function GetTableColumns(string $table): mixed
    {
        static::_connect();

        return static::$connection->GetTableColumns($table);
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
     * @param string $table
     * @return mixed
     */
    public static function GetUniqueKeys(string $table): mixed
    {
        static::_connect();

        return static::$connection->GetUniqueKeys($table);
    }

    /**
     * @param string $table
     * @return mixed
     */
    public static function GetForeignKeys(string $table): mixed
    {
        static::_connect();

        return static::$connection->GetForeignKeys($table);
    }

    /**
     * @param string $table
     * @return MySQL_ForeignKey[]
     */
    public static function GetLinkedTables(string $table): array
    {
        static::_connect();

        return static::$connection->GetLinkedTables($table);
    }

    /**
     * @param string $table
     * @return mixed
     */
    public static function GetPrimaryKey(string $table): mixed
    {
        static::_connect();

        return static::$connection->GetPrimaryKey($table);
    }

    /**
     * @return mixed
     */
    public static function GetStoredProcs(): mixed
    {
        static::_connect();

        return static::$connection->GetStoredProcs();
    }

    /**
     * @param string $specific_name
     * @return mixed
     */
    public static function GetStoredProcParams(string $specific_name): mixed
    {
        static::_connect();

        return static::$connection->GetStoredProcParams($specific_name);
    }

    /**
     * @param string $sql
     * @param array|null $params
     * @return mixed
     */
    public static function EscapeQuery(string $sql, ?array $params = null): mixed
    {
        static::_connect();

        return static::$connection->EscapeQuery($sql, $params);
    }

    /**
     * @param string $sql
     * @param array|null $params
     * @param bool $large
     * @return QueryExecuteResult
     */
    public static function Execute(string $sql, ?array $params = null, bool $large = false): QueryExecuteResult
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
        return new QueryExecuteResult();
    }

    /**
     * @param QuickDRYUser $user
     * @return bool
     */
    public function CanDelete(QuickDRYUser $user): bool
    {
        return false;
    }

    /**
     * @param string $sql
     * @param array|null $params
     * @param callable|null $map_function
     * @return array
     */
    public static function QueryMap(
        string   $sql,
        ?array   $params = null,
        callable $map_function = null): array
    {
        $res = self::Query($sql, $params, false, $map_function);
        if (isset($res['error'])) {
            Debug($res);
        }
        return $res;
    }

    /**
     * @param string $sql
     * @param array|null $params
     * @param bool $objects_only
     * @param callable|null $map_function
     * @return array
     */
    public static function Query(
        string   $sql,
        array    $params = null,
        bool     $objects_only = false,
        callable $map_function = null): array
    {
        static::_connect();

        $return_type = null;
        if ($objects_only)
            $return_type = get_called_class();

        if (isset(static::$database))
            static::$connection->SetDatabase(static::$database);

        return static::$connection->Query($sql, $params, $return_type, $map_function);
    }

    /**
     * @return int|string
     */
    public static function LastID(): int|string
    {
        static::_connect();

        return static::$connection->LastID();
    }

    /**
     * @param QuickDRYUser $User
     * @param string $ChangeLogClass
     * @return QueryExecuteResult
     */
    public function Remove(
        QuickDRYUser $User,
        string $ChangeLogClass = ChangeLog::class
    ): QueryExecuteResult
    {
        if (!$this->CanDelete($User)) {
            return new QueryExecuteResult();
        }

        // if this instance wasn't loaded from the database
        // don't try to remove it
        if (!$this->_from_db) {
            return new QueryExecuteResult();
        }

        if ($this->HasChangeLog()) {
            $uuid = $this->GetUUID();

            if ($uuid) {
                /* @var ChangeLog $cl */
                $cl = new $ChangeLogClass();
                $cl->host = static::$DB_HOST;
                $cl->database = static::$database;
                $cl->table = static::$table;
                $cl->uuid = $uuid;
                $cl->changes = json_encode($this->_change_log);
                $cl->user_id = $User->GetUUID();
                $cl->created_at = Dates::Timestamp();
                $cl->object_type = static::class;
                $cl->is_deleted = true;
                $cl->Save();
            }
        }


        $params = [];
        $where = [];
        // rows are removed based on the columns which
        // make the row unique
        if (sizeof(static::$_primary) > 0) {
            foreach (static::$_primary as $column) {
                $where[] = $column . ' = {{}}';
                $params[] = $this->{$column};
            }
        } elseif (sizeof(static::$_unique) > 0) {
            foreach (static::$_unique as $column) {
                $where[] = $column . ' = {{}}';
                $params[] = $this->{$column};
            }
        } else
            exit('unique or primary key required');


        $sql = '
			DELETE FROM
				' . static::$table . '
			WHERE
				' . implode(' AND ', $where) . '
		';
        $res = static::Execute($sql, $params);

        if (method_exists($this, 'SolrRemove'))
            $this->SolrRemove();

        return $res;
    }

    /**
     * @param string $col
     * @param string|null $val
     *
     * @return array
     */
    #[ArrayShape(['col' => 'string', 'val' => 'null|string|string[]'])] protected static function _parse_col_val(string $col, string $val = null): array
    {
        // extra + symbols allow us to do AND on the same column
        $col = str_replace('+', '', $col);
        $col = '`' . $col . '`';

        // adding a space to ensure that "in_" is not mistaken for an IN query
        // and the parameter must START with the special SQL command
        if (str_starts_with($val, '{BETWEEN} ')) {
            $val = trim(Strings::RemoveFromStart('{BETWEEN}', $val));
            $val = explode(',', $val);
            $col = $col . ' BETWEEN {{}} AND {{}}';
        } elseif (str_starts_with($val, '{DATE} ')) {
            $col = 'DATE(' . $col . ') = {{}}';
            $val = trim(Strings::RemoveFromStart('{DATE}', $val));
        } elseif (str_starts_with($val, '{YEAR} ')) {
            $col = 'YEAR(' . $col . ') = {{}}';
            $val = trim(Strings::RemoveFromStart('{YEAR}', $val));
        } elseif (str_starts_with($val, '{IN} ')) {
            $val = explode(',', trim(Strings::RemoveFromStart('{IN} ', $val)));
            if (($key = array_search('null', $val)) !== false) {
                $col = '(' . $col . ' IS NULL OR ' . $col . 'IN (' . Strings::StringRepeatCS('{{}}', sizeof($val) - 1) . '))';
                unset($val[$key]);
            } else {
                $col = $col . 'IN (' . Strings::StringRepeatCS('{{}}', sizeof($val)) . ')';
            }
        } elseif (str_starts_with($val, '{NOT IN} ')) {
            $val = explode(',', trim(Strings::RemoveFromStart('{NOT IN} ', $val)));
            if (($key = array_search('null', $val)) !== false) {
                $col = '(' . $col . ' IS NOT NULL OR ' . $col . ' NOT IN (' . Strings::StringRepeatCS('{{}}', sizeof($val) - 1) . '))';
                unset($val[$key]);
            } else {
                $col = $col . 'NOT IN (' . Strings::StringRepeatCS('{{}}', sizeof($val)) . ')';
            }
        } elseif (str_starts_with($val, '{NLIKE} ')) {
            $col = $col . ' NOT LIKE {{}} ';
            $val = trim(Strings::RemoveFromStart('{NLIKE} ', $val));
        } elseif (str_starts_with($val, '{NILIKE} ')) {
            $col = 'LOWER(' . $col . ')' . ' NOT ILIKE {{}} ';
            $val = strtolower(trim(Strings::RemoveFromStart('{NILIKE} ', $val)));
        } elseif (str_starts_with($val, '{ILIKE} ')) {
            $col = 'LOWER(' . $col . ')' . ' ILIKE {{}} ';
            $val = strtolower(trim(Strings::RemoveFromStart('{ILIKE} ', $val)));
        } elseif (str_starts_with($val, '{LIKE} ')) {
            $col = 'LOWER(' . $col . ')' . ' LIKE LOWER({{}}) ';
            $val = trim(Strings::RemoveFromStart('{LIKE} ', $val));
        } elseif (stristr($val, '<=') !== false) {
            $col = $col . ' <= {{}} ';
            $val = trim(Strings::RemoveFromStart('<=', $val));
        } elseif (stristr($val, '>=') !== false) {
            $col = $col . ' >= {{}} ';
            $val = trim(Strings::RemoveFromStart('>=', $val));
        } elseif (stristr($val, '<>') !== false) {
            $val = trim(Strings::RemoveFromStart('<>', $val));
            if ($val !== 'null')
                $col = $col . ' <> {{}} ';
            else
                $col = $col . ' IS NOT NULL';
        } elseif (stristr($val, '<') !== false) {
            $col = $col . ' < {{}} ';
            $val = trim(Strings::RemoveFromStart('<', $val));
        } elseif (stristr($val, '>') !== false) {
            $col = $col . ' > {{}} ';
            $val = trim(Strings::RemoveFromStart('>', $val));
        } elseif (strtolower($val) !== 'null') {
            $col = $col . ' = {{}} ';
        } else {
            $col = $col . ' IS NULL ';
        }


        return ['col' => $col, 'val' => $val];
    }

    /**
     * @param array|null $where
     *
     * @return static|null
     */
    protected static function _Get(array $where = null): ?static
    {
        $params = [];
        $t = [];
        foreach ($where as $c => $v) {
            $cv = self::_parse_col_val($c, $v);
            $v = $cv['val'];

            if (is_array($v)) {
                foreach ($v as $vv) {
                    $params[] = $vv;
                }
            } elseif ($v !== 'null') {
                $params[] = $v;
            }

            $t[] = $cv['col'];
        }
        $sql_where = implode(' AND ', $t);

        $sql = '
			SELECT
				*
			FROM
				`' . static::$table . '`
			WHERE
				' . $sql_where . '
			';

        $res = static::Query($sql, $params, true);
        foreach ($res as $t) {
            return $t;
        }
        return null;
    }

    /**
     * @param array|null $where
     * @param array|null $order_by
     * @param int|null $limit
     *
     * @return array|null
     */
    protected static function _GetAll(?array $where = null, array $order_by = null, int $limit = null): ?array
    {
        $params = [];

        $sql_order = [];
        if (is_array($order_by)) {
            foreach ($order_by as $col => $dir) {
                $sql_order[] .= '`' . trim($col) . '` ' . $dir;
            }
            $sql_order = 'ORDER BY ' . implode(', ', $sql_order);
        } else {
            $sql_order = '';
        }

        $sql_where = '1=1';
        if (is_array($where)) {
            $t = [];
            foreach ($where as $c => $v) {
                $c = str_replace('+', '', $c);
                $cv = self::_parse_col_val($c, $v);
                $v = $cv['val'];

                if (is_array($v)) {
                    foreach ($v as $vv) {
                        $params[] = $vv;
                    }
                } elseif ($v !== 'null') {
                    $params[] = $v;
                }

                $t[] = $cv['col'];
            }
            $sql_where = implode(' AND ', $t);
        }

        $sql = '
			SELECT
				*
			FROM
				`' . static::$table . '`
			WHERE
				' . $sql_where . '
				' . $sql_order . '
		';

        if ($limit) {
            $sql .= ' LIMIT ' . ($limit * 1.0);
        }

        return static::Query($sql, $params, true);
    }

    /**
     * @param array $where
     * @return int
     */
    protected static function _GetCount(array $where = []): int
    {
        $sql_where = '1=1';
        $params = [];
        if (is_array($where)) {
            $t = [];
            foreach ($where as $c => $v) {
                $cv = self::_parse_col_val($c, $v);
                $v = $cv['val'];

                if (is_array($v)) {
                    foreach ($v as $vv) {
                        $params[] = $vv;
                    }
                } elseif ($v !== 'null') {
                    $params[] = $v;
                }

                $t[] = $cv['col'];
            }
            $sql_where = implode(' AND ', $t);
        }

        $sql = '
			SELECT
				COUNT(*) AS cnt
			FROM
				`' . static::$table . '`
			WHERE
				' . $sql_where . '
		';

        $res = static::Query($sql, $params);
        foreach ($res['data'] as $r) {
            return $r['cnt'];
        }

        return 0;
    }

    /**
     * @param array|null $where
     * @param array|null $order_by
     * @param int|null $page
     * @param int|null $per_page
     * @param array|null $left_join
     * @param int|null $limit
     *
     * @return array
     */
    #[ArrayShape(['count' => 'int|mixed', 'items' => 'array', 'sql' => 'string', 'res' => 'array'])] protected static function _GetAllPaginated(
        array $where = null,
        array $order_by = null,
        int   $page = null,
        int   $per_page = null,
        array $left_join = null,
        int   $limit = null): array
    {
        $params = [];

        $sql_order = [];
        if (is_array($order_by) && sizeof($order_by)) {
            foreach ($order_by as $col => $dir) {
                if (stristr($col, '.') !== false) {
                    $col = explode('.', $col);
                    $sql_order[] .= '`' . trim($col[0]) . '`.`' . trim($col[1]) . '` ' . $dir;
                } else {
                    if (is_array($col)) {
                        Debug(['QuickDRY Error' => '$col cannot be array', $col]);
                    }
                    $sql_order[] .= '`' . trim($col) . '` ' . $dir;
                }
            }
            $sql_order = 'ORDER BY ' . implode(', ', $sql_order);
        }

        $sql_where = '1=1';
        if (is_array($where) && sizeof($where)) {
            $t = [];
            foreach ($where as $c => $v) {
                $c = str_replace('+', '', $c);
                $c = str_replace('.', '`.`', $c);
                $cv = self::_parse_col_val($c, $v);
                $v = $cv['val'];

                if (strtolower($v) !== 'null') {
                    $params[] = $cv['val'];
                }
                $t[] = $cv['col'];
            }
            $sql_where = implode(' AND ', $t);
        }

        $sql_left = '';
        if (is_array($left_join)) {
            foreach ($left_join as $join) {
                if (!isset($join['database'])) {
                    Debug($join, 'invalid join');
                }
                $sql_left .= 'LEFT JOIN  `' . $join['database'] . '`.`' . $join['table'] . '` AS ' . $join['as'] . ' ON ' . $join['on']
                    . "\r\n";
            }
        }

        if (!$limit) {
            $sql = '
				SELECT
					COUNT(*) AS num
				FROM
					`' . static::$database . '`.`' . static::$table . '`
					' . $sql_left . '
				WHERE
					' . $sql_where . '
				';
        } else {
            $sql = '
				SELECT COUNT(*) AS num FROM (SELECT * FROM `' . static::$database . '`.`' . static::$table . '`
					' . $sql_left . '
				WHERE
					' . $sql_where . '
				LIMIT ' . $limit . '
				) AS c
			';
        }

        $res = static::Query($sql, $params);
        $count = $res['data'][0]['num'] ?? 0;
        $list = [];
        if ($count > 0) {
            $sql = '
				SELECT
					`' . static::$table . '`.*
				FROM
					`' . static::$database . '`.`' . static::$table . '`
					' . $sql_left . '
				WHERE
					 ' . $sql_where . '
					' . $sql_order . '
			';
            if ($per_page != 0) {
                $sql .= '
				LIMIT ' . ($per_page * $page) . ', ' . $per_page . '
				';
            }

            $list = static::Query($sql, $params, true);
        }
        return ['count' => $count, 'items' => $list, 'sql' => $sql, 'res' => $res];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    protected static function IsNumeric(string $name): bool
    {
        return match (static::$prop_definitions[$name]['type']) {
            'tinyint(1)', 'tinyint(1) unsigned', 'int(10) unsigned', 'bigint unsigned', 'decimal(18,2)', 'int(10)', 'uinit' => true,
            default => false,
        };
    }

    /**
     * @param string $name
     * @param $value
     *
     * @return float|int|string|null
     * @throws Exception
     */
    protected static function StrongType(string $name, $value): float|int|string|null
    {
        if (is_object($value) || is_array($value))
            return null;

        if (strcasecmp($value, 'null') == 0) {
            if (!static::$prop_definitions[$name]['is_nullable']) {
                throw new Exception($name . ' cannot be null');
            }
            return null;
        }


        switch (static::$prop_definitions[$name]['type']) {
            case 'date':
                return $value ? Dates::Datestamp($value) : null;

            case 'tinyint(1)':
                if (is_null($value)) {
                    return null;
                }
                if (!is_numeric($value)) {
                    $value = 0;
                }
                return $value ? 1 : 0;

            case 'unit':
            case 'decimal(18,2)':
            case 'double':
            case 'int(10)':
                if (!is_numeric($value)) {
                    $value = 0;
                }
                return $value * 1.0;

            case 'timestamp':
            case 'datetime':
                return $value ? Dates::Timestamp($value) : null;
        }
        return $value;
    }

    /**
     * @param bool $force_insert
     * @param string $ChangeLogClass
     * @param QuickDRYUser|null $CurrentUser
     * @return QueryExecuteResult
     */
    protected function _Save(
        bool $force_insert = false,
        string $ChangeLogClass = ChangeLog::class,
        ?QuickDRYUser $CurrentUser = null
    ): QueryExecuteResult
    {
        global $Web;

        if (!sizeof($this->_change_log)) {
            return new QueryExecuteResult();
        }

        $primary = isset(static::$_primary[0]) && static::$_primary[0] ? static::$_primary[0] : null;
        $params = [];

        if (sizeof(static::$_unique)) { // if we have a unique key defined then check it and load the object if it exists

            foreach (static::$_unique as $unique) {
                $params = [];
                $unique_set = 0;
                foreach ($unique as $col) {
                    if (is_null($this->$col))
                        $params[$col] = 'null';
                    else {
                        $params[$col] = $this->$col;
                        $unique_set++;
                    }
                }

                if ($unique_set && !$this->$primary) {
                    $type = static::class;
                    if (!method_exists($type, 'Get')) {
                        Debug($type . '::Get');
                    }
                    $t = $type::Get($params);

                    if (!is_null($t)) {
                        if ($t->$primary)
                            $this->$primary = $t->$primary;
                        $vars = $t->ToArray();
                        foreach ($vars as $k => $v) {
                            if (isset($this->$k) && is_null($this->$k)) {
                                // if the current object value is null, fill it in with the existing object's info
                                $this->$k = $v;
                            }
                        }
                    }
                }
            }
        }


        $changed_only = false;
        if (!$primary || !$this->$primary || $force_insert) {
            $sql = '
				INSERT INTO
			';
        } else {
            $changed_only = true;
            // ignore cases where the unique key isn't sufficient to avoid duplicate inserts -- removed 8/30/2019 - handle the error in code
            $sql = '
				UPDATE
			';
        }

        $sql .= '
					`' . static::$database . '`.`' . static::$table . '`
				SET
				';


        foreach ($this->props as $name => $value) {
            if ($changed_only && !isset($this->_change_log[$name])) {
                continue;
            }

            $st_value = null;
            try {
                $st_value = static::StrongType($name, $value);
            } catch (Exception $ex) {
                Debug($ex);
            }

            if (strcmp($name, $primary) == 0 && !$this->$primary && !$force_insert) {
                continue;
            }

            if (is_null($st_value) || strtolower(trim($st_value)) === 'null')
                $sql .= '`' . $name . '` = NULL,';
            else {
                $sql .= '`' . $name . '` = {{}},';
                $params[] = $st_value;
            }
        }

        $sql = substr($sql, 0, strlen($sql) - 1);

        if ($primary && $this->$primary && !$force_insert) {
            $sql .= '
				WHERE
					`' . $primary . '` = {{}}
				';
            $params[] = $this->$primary;
        }

        $res = static::Execute($sql, $params);

        if ($primary && !$this->$primary)
            $this->$primary = $res->last_id;

        if ($this->HasChangeLog()) {
            $uuid = $this->GetUUID();
            if ($uuid) {
                $cl = new $ChangeLogClass();
                $cl->host = static::$DB_HOST;
                $cl->database = static::$database;
                $cl->table = static::$table;
                $cl->uuid = $uuid;
                $cl->changes = json_encode($this->_change_log);
                $cl->user_id = $CurrentUser?->GetUUID();
                $cl->created_at = Dates::Timestamp();
                $cl->object_type = static::TableToClass(static::$DatabasePrefix, static::$table, static::$LowerCaseTable, static::$DatabaseTypePrefix);
                $cl->is_deleted = false;
                $cl->Save();
            }
        }
        $this->_from_db = true;
        return $res;
    }

    /**
     * @param bool $return_query
     *
     * @return SQL_Query|QueryExecuteResult
     * @throws Exception
     */
    protected function _Insert(bool $return_query = false): SQL_Query|QueryExecuteResult
    {
        $primary = static::$_primary[0] ?? 'id';

        $sql = '
INSERT INTO
    `' . static::$database . '`.`' . static::$table . '`
';
        $props = [];
        $params = [];
        $qs = [];
        foreach ($this->props as $name => $value) {
            if (strcmp($name, $primary) == 0 && !$this->$primary) {
                continue;
            }

            $props[] = $name;

            $st_value = static::StrongType($name, $value);


            if (!is_object($value) && (is_null($st_value) || strtolower(trim($value)) === 'null') && (self::IsNumeric($name) || (!self::IsNumeric($name) && !$this->PRESERVE_NULL_STRINGS))) {
                $qs[] = 'NULL #' . $name . PHP_EOL;
            } else {
                $qs[] = '{{}} #' . $name . PHP_EOL;
                $params[] = $st_value; // reverted because MySQL doesn't use EscapeString
            }

        }
        $sql .= '(`' . implode('`,`', $props) . '`) VALUES (' . implode(',', $qs) . ')';


        if ($return_query) {
            return new SQL_Query($sql, $params);
        }
        return static::Execute($sql, $params);
    }

    /**
     * @param bool $return_query
     * @return QueryExecuteResult|SQL_Query|null
     * @throws Exception
     */
    protected function _Update(bool $return_query): SQL_Query|QueryExecuteResult|null
    {
        if (!sizeof($this->_change_log)) {
            return null;
        }

        $primary = static::$_primary[0] ?? 'id';

        $sql = '
UPDATE
    `' . static::$database . '`.`' . static::$table . '`
SET
';
        $props = [];
        $params = [];
        foreach ($this->props as $name => $value) {
            if (!isset($this->_change_log[$name])) {
                continue;
            }
            if (strcmp($name, $primary) == 0) continue;

            $st_value = static::StrongType($name, $value);


            if (!is_object($value) && (is_null($st_value) || strtolower(trim($value)) === 'null') && (self::IsNumeric($name) || (!self::IsNumeric($name) && !$this->PRESERVE_NULL_STRINGS))) {
                $props[] = '`' . $name . '` = NULL # ' . $name . PHP_EOL;
            } else {
                $props[] = '`' . $name . '` = {{}} #' . $name . PHP_EOL;
                $params[] = $st_value;
            }
        }
        $sql .= implode(',', $props);

        $sql .= '
WHERE
    ' . $primary . ' = {{}}
';

        $params[] = $this->$primary;


        if ($return_query) {
            return new SQL_Query($sql, $params);
        }


        $res = static::Execute($sql, $params);

        if (!$this->$primary)
            $this->$primary = static::LastID();

        return $res;
    }
}