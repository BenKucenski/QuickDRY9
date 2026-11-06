<?php
declare(strict_types=1);

namespace QuickDRY\Connectors\mysql;

use DateTime;
use Exception;
use InvalidArgumentException;
use JetBrains\PhpStorm\ArrayShape;
use models\ChangeLog;
use QuickDRY\Connectors\QueryExecuteResult;
use QuickDRY\Connectors\SQL_Base;
use QuickDRY\Connectors\SQL_Query;
use QuickDRY\Interfaces\ICurrentUser;
use QuickDRY\Interfaces\ISQLConnection;
use QuickDRY\Interfaces\ISQLCore;
use QuickDRY\Utilities\Dates;
use QuickDRY\Utilities\Strings;
use QuickDRY\Web\ElementID;

/**
 * Class MySQL_Core
 */
class MySQL_Core extends SQL_Base implements ISQLCore
{
    protected static ?string $DB_HOST = null;
    protected static string $DatabasePrefix = '';
    protected static int $LowerCaseTable = 0;
    protected static string $DatabaseTypePrefix = '';
    protected static array $_primary = [];
    protected static array $_unique = [];
    protected static array $prop_definitions = [];

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
        if ($value instanceof DateTime) {
            $value = Dates::Timestamp($value, '');
        }

        if ($value || $force_value) {
            return $value;
        }

        if ($this->$column_name instanceof DateTime) {
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
     * @return QueryExecuteResult|SQL_Query|null
     * @throws Exception
     */
    public function Insert(bool $return_query = false): SQL_Query|null|QueryExecuteResult
    {
        return $this->_Insert($return_query);
    }

    /**
     * @param bool $return_query
     * @return QueryExecuteResult|SQL_Query|null
     * @throws Exception
     */
    public function Update(bool $return_query = false): SQL_Query|null|QueryExecuteResult
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
    public static function GetUniqueKeys(string $table_name): mixed
    {
        static::_connect();

        return static::$connection->GetUniqueKeys($table_name);
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
     * @return MySQL_ForeignKey[]
     */
    public static function GetLinkedTables(string $table_name): array
    {
        static::_connect();

        return static::$connection->GetLinkedTables($table_name);
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
     * @return array
     */
    public static function GetStoredProcs(): array
    {
        static::_connect();

        return static::$connection->GetStoredProcs();
    }

    /**
     * @param mixed $stored_proc
     * @return array
     */
    public static function GetStoredProcParams(mixed $stored_proc): array
    {
        static::_connect();

        return static::$connection->GetStoredProcParams($stored_proc);
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
            Exception($ex);
        }
    }

    /**
     * @return bool
     */
    public function CanDelete(): bool
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
        string    $sql,
        ?array    $params = null,
        ?callable $map_function = null): array
    {
        $res = self::Query($sql, $params, false, $map_function);
        if (isset($res['error'])) {
            Exception($res);
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
        string    $sql,
        ?array    $params = null,
        bool      $objects_only = false,
        ?callable $map_function = null): array
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

        if (isset(static::$database))
            static::$connection->SetDatabase(static::$database);

        return static::$connection->Query($sql, $params, $map_function);
    }

    /**
     * @param int|null $LastID
     * @return int|string|null
     */
    public static function LastID(?int $LastID = null): int|string|null
    {
        static::_connect();

        return static::$connection->LastID();
    }

    /**
     * @return QueryExecuteResult
     */
    public function Remove(): QueryExecuteResult
    {
        if (!$this->CanDelete()) {
            return new QueryExecuteResult();
        }

        // if this instance wasn't loaded from the database
        // don't try to remove it
        if (!$this->_from_db) {
            return new QueryExecuteResult();
        }

        if ($this->HasChangeLog()) {
            $uuid = $this->PrimaryKey();

            if ($uuid) {
                ChangeLog::Delete($this);
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
     * @param string|null $col
     * @param string|null $val
     *
     * @return array
     */
    #[ArrayShape(['col' => 'string', 'val' => 'null|string|string[]'])]
    protected static function _parse_col_val(
        ?string $col,
        ?string $val = null
    ): array
    {
        if (!$col) {
            throw new InvalidArgumentException('Column name cannot be empty');
        }

        // normalize column name
        $col = str_replace('+', '', $col);
        $col = '`' . $col . '`';
        $paramName = str_replace('`', '', $col); // safe base name for parameter(s)

        if (is_null($val)) {
            $col .= ' IS NULL ';
            return ['col' => $col, 'val' => $val];
        }

        if (str_starts_with($val, '{BETWEEN} ')) {
            $vals = explode(',', trim(Strings::RemoveFromStart('{BETWEEN}', $val)));
            $col .= " BETWEEN :{$paramName}_from AND :{$paramName}_to";
            $val = [
                "{$paramName}_from" => $vals[0] ?? null,
                "{$paramName}_to"   => $vals[1] ?? null,
            ];
        } elseif (str_starts_with($val, '{DATE} ')) {
            $col = "DATE($col) = :{$paramName}_date";
            $val = ["{$paramName}_date" => trim(Strings::RemoveFromStart('{DATE}', $val))];
        } elseif (str_starts_with($val, '{YEAR} ')) {
            $col = "YEAR($col) = :{$paramName}_year";
            $val = ["{$paramName}_year" => trim(Strings::RemoveFromStart('{YEAR}', $val))];
        } elseif (str_starts_with($val, '{IN} ')) {
            $vals = explode(',', trim(Strings::RemoveFromStart('{IN} ', $val)));
            $placeholders = [];
            foreach ($vals as $i => $v) {
                $placeholders[] = ":{$paramName}_in" . $i;
                $vals[$i] = [$paramName . '_in' . $i => $v];
            }
            $col .= ' IN (' . implode(', ', $placeholders) . ')';
            $val = array_merge(...$vals);
        } elseif (str_starts_with($val, '{NOT IN} ')) {
            $vals = explode(',', trim(Strings::RemoveFromStart('{NOT IN} ', $val)));
            $placeholders = [];
            foreach ($vals as $i => $v) {
                $placeholders[] = ":{$paramName}_nin" . $i;
                $vals[$i] = [$paramName . '_nin' . $i => $v];
            }
            $col .= ' NOT IN (' . implode(', ', $placeholders) . ')';
            $val = array_merge(...$vals);
        } elseif (str_starts_with($val, '{NLIKE} ')) {
            $col .= ' NOT LIKE :' . $paramName . '_nlike';
            $val = [$paramName . '_nlike' => trim(Strings::RemoveFromStart('{NLIKE} ', $val))];
        } elseif (str_starts_with($val, '{NILIKE} ')) {
            $col = "LOWER($col) NOT ILIKE :{$paramName}_nilike";
            $val = [$paramName . '_nilike' => strtolower(trim(Strings::RemoveFromStart('{NILIKE} ', $val)))];
        } elseif (str_starts_with($val, '{ILIKE} ')) {
            $col = "LOWER($col) ILIKE :{$paramName}_ilike";
            $val = [$paramName . '_ilike' => strtolower(trim(Strings::RemoveFromStart('{ILIKE} ', $val)))];
        } elseif (str_starts_with($val, '{LIKE} ')) {
            $col = "LOWER($col) LIKE LOWER(:{$paramName}_like)";
            $val = [$paramName . '_like' => trim(Strings::RemoveFromStart('{LIKE} ', $val))];
        } elseif (str_starts_with($val, '<=')) {
            $col .= " <= :{$paramName}_lte";
            $val = [$paramName . '_lte' => trim(Strings::RemoveFromStart('<=', $val))];
        } elseif (str_starts_with($val, '>=')) {
            $col .= " >= :{$paramName}_gte";
            $val = [$paramName . '_gte' => trim(Strings::RemoveFromStart('>=', $val))];
        } elseif (str_starts_with($val, '<>')) {
            $v = trim(Strings::RemoveFromStart('<>', $val));
            if (strtolower($v) !== 'null') {
                $col .= " <> :{$paramName}_neq";
                $val = [$paramName . '_neq' => $v];
            } else {
                $col .= ' IS NOT NULL';
                $val = null;
            }
        } elseif (str_starts_with($val, '<')) {
            $col .= " < :{$paramName}_lt";
            $val = [$paramName . '_lt' => trim(Strings::RemoveFromStart('<', $val))];
        } elseif (str_starts_with($val, '>')) {
            $col .= " > :{$paramName}_gt";
            $val = [$paramName . '_gt' => trim(Strings::RemoveFromStart('>', $val))];
        } elseif (strtolower($val) !== 'null') {
            $col .= " = :{$paramName}_eq";
            $val = [$paramName . '_eq' => $val];
        } else {
            $col .= ' IS NULL';
            $val = null;
        }

        return ['col' => $col, 'val' => $val];
    }

    /**
     * @param array|null $where
     *
     * @return static|null
     */
    protected static function _Get(?array $where = null): ?static
    {
        $params = [];
        $t = [];
        foreach ($where as $c => $v) {
            $cv = self::_parse_col_val($c, is_null($v) ? null : (string)$v);
            $v = $cv['val'];

            if (is_array($v)) {
                foreach ($v as $kv => $vv) {
                    $params[$kv] = $vv;
                }
            } elseif ($v && strtolower($v) !== 'null') {
                $params[$c] = $cv['val'];
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
        if (isset($res['error'])) {
            Exception($res);
        }

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
    protected static function _GetAll(?array $where = null, ?array $order_by = null, ?int $limit = null): ?array
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
                $cv = self::_parse_col_val($c, is_null($v) ? null : (string)$v);
                $v = $cv['val'];

                if (is_array($v)) {
                    foreach ($v as $kv => $vv) {
                        $params[$kv] = $vv;
                    }
                } elseif ($v && strtolower($v) !== 'null') {
                    $params[$c] = $cv['val'];
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

//        echo '<pre>' . print_r([$sql,  $params, self::EscapeQuery($sql, $params)], true) . '</pre>';

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
                $cv = self::_parse_col_val($c, (string)$v);
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
            return (int)$r['cnt'];
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
    #[ArrayShape(['count' => 'int|mixed', 'items' => 'array', 'sql' => 'string', 'res' => 'array'])]
    protected static function _GetAllPaginated(
        ?array $where = null,
        ?array $order_by = null,
        ?int   $page = null,
        ?int   $per_page = null,
        ?array $left_join = null,
        ?int   $limit = null
    ): array
    {
        $params = [];

        $sql_order = '';
        if (is_array($order_by) && sizeof($order_by)) {
            $sql_order_by = [];
            foreach ($order_by as $col => $dir) {
                if (stristr($col, '.') !== false) {
                    $col = explode('.', $col);
                    $sql_order_by[] .= '`' . trim($col[0]) . '`.`' . trim($col[1]) . '` ' . $dir;
                } else {
                    if (is_array($col)) {
                        Exception(['QuickDRY Error' => '$col cannot be array', $col]);
                    }
                    $sql_order_by[] .= '`' . trim($col) . '` ' . $dir;
                }
            }
            $sql_order = 'ORDER BY ' . implode(', ', $sql_order_by);
        }

        $sql_where = '1=1';
        if (is_array($where) && sizeof($where)) {
            $t = [];
            foreach ($where as $c => $v) {
                $c = str_replace('+', '', $c);
                $c = str_replace('.', '`.`', $c);
                $cv = self::_parse_col_val($c, is_null($v) ? null : (string)$v);
                $v = $cv['val'];

                if (is_array($v)) {
                    foreach ($v as $kv => $vv) {
                        $params[$kv] = $vv;
                    }
                } elseif ($v && strtolower($v) !== 'null') {
                    $params[$c] = $cv['val'];
                }
                $t[] = $cv['col'];
            }
            $sql_where = implode(' AND ', $t);
        }

        $sql_left = '';
        if (is_array($left_join)) {
            foreach ($left_join as $join) {
                if (!isset($join['database'])) {
                    Exception($join, 'invalid join');
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

//        echo '<pre>' . print_r([$sql,  $params, self::EscapeQuery($sql, $params)], true) . '</pre>';

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
        return [
            'count' => (int)$count,
            'items' => $list,
            'sql'   => $sql,
            'res'   => $res,
        ];
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
     * @return mixed
     * @throws Exception
     */
    protected static function StrongType(string $name, $value): mixed
    {
        if (is_object($value) || is_array($value)) {
            return null;
        }

        if ($value && strcasecmp((string)$value, 'null') == 0) {
            if (!static::$prop_definitions[$name]['is_nullable']) {
                throw new Exception($name . ' cannot be null');
            }
            return null;
        }

        if (is_null($value) && static::$prop_definitions[$name]['is_nullable']) {
            return null;
        }

        if (str_starts_with(static::$prop_definitions[$name]['type'], 'varchar')) {
            return (string)$value;
        }

        if (str_starts_with(static::$prop_definitions[$name]['type'], 'char')) {
            return (string)$value;
        }

        if (str_starts_with(static::$prop_definitions[$name]['type'], 'enum(')) {
            return (string)$value;
        }
        if (str_starts_with(static::$prop_definitions[$name]['type'], 'binary')) {
            return (string)$value;
        }

        if (str_starts_with(static::$prop_definitions[$name]['type'], 'int')) {
            return (int)Strings::Numeric($value);
        }

        if (str_starts_with(static::$prop_definitions[$name]['type'], 'tinyint')) {
            return (int)Strings::Numeric($value);
        }

        if (str_starts_with(static::$prop_definitions[$name]['type'], 'decimal')) {
            return (float)Strings::Numeric($value);
        }

        if (str_starts_with(static::$prop_definitions[$name]['type'], 'double')) {
            return (float)Strings::Numeric($value);
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

            case 'bigint':
            case 'smallint unsigned':
            case 'bigint unsigned':
                if (is_null($value) && static::$prop_definitions[$name]['is_nullable']) {
                    return null;
                }
                return (int)Strings::Numeric($value);

            case 'unit':
            case 'float':
            case 'double':
                if (is_null($value) && static::$prop_definitions[$name]['is_nullable']) {
                    return null;
                }
                return (float)Strings::Numeric($value);

            case 'timestamp':
            case 'datetime':
                return $value ? Dates::Timestamp($value) : null;

            case 'text':
            case 'json':
            case 'mediumtext':
            case 'longtext':
            case 'longblob':
                return (string)$value;

            default:
                Exception('Unknown type ' . static::$prop_definitions[$name]['type'] . ' for value ' . $value);
        }
    }

    /**
     * @param bool $force_insert
     *
     * @return QueryExecuteResult
     */
    protected function _Save(bool $force_insert = false): QueryExecuteResult
    {
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
                        Exception($type . '::Get');
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
                Exception($ex);
            }

            if ($primary && strcmp($name, $primary) == 0 && !$this->$primary && !$force_insert) {
                continue;
            }

            if (is_bool($value)) {
                $sql .= '`' . $name . '` = :' . $name . ',';
                $params[$name] = $st_value;
            } elseif (is_null($st_value) || strtolower(trim((string)$st_value)) === 'null')
                $sql .= '`' . $name . '` = NULL,';
            else {
                $sql .= '`' . $name . '` = :' . $name . ',';
                $params[$name] = $st_value;
            }
        }

        $sql = substr($sql, 0, strlen($sql) - 1);

        if ($primary && $this->$primary && !$force_insert) {
            $sql .= '
				WHERE
					`' . $primary . '` = :' . $primary . '
				';
            $params[$primary] = $this->$primary;
        }

        $res = static::Execute($sql, $params);

        if ($primary && !$this->$primary)
            $this->$primary = $res->last_id;

        if ($this->HasChangeLog()) {
            $uuid = $this->PrimaryKey();
            if ($uuid) {
                ChangeLog::Create($this);

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
                $qs[] = ':' . $name . ' #' . $name . PHP_EOL;
                $params[$name] = $st_value; // reverted because MySQL doesn't use EscapeString
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

    /**
     * @return array
     */
    public static function GetDatabases(): array
    {
        Exception('Not Implemented');
    }

    /**
     * @param string $table_name
     * @return array
     */
    public static function GetTableIndexes(string $table_name): array
    {
        Exception('Not Implemented');
    }

    /**
     * @return array
     */
    public static function GetTriggers(): array
    {
        Exception('Not Implemented');
    }

    /**
     * @return array
     */
    public static function GetDefinitions(): array
    {
        Exception('Not Implemented');
    }

    /**
     * @return string
     */
    public static function GUID(): string
    {
        Exception('Not Implemented');
    }

    /**
     * @return ISQLConnection|null
     */
    public static function getConnection(): ?ISQLConnection
    {
        return static::$connection;
    }
}