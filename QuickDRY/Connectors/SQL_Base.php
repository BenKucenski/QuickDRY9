<?php
declare(strict_types=1);

namespace QuickDRY\Connectors;

use Exception;
use JsonSerializable;
use models\ChangeLogHistory;
use QuickDRY\Interfaces\IChangeLogHistory;
use QuickDRY\Utilities\SimpleExcel;
use QuickDRY\Utilities\SimpleExcel_Column;
use DateTime;
use QuickDRY\Utilities\Dates;
use QuickDRY\Web\ElementID;
use ReflectionException;
use ReflectionObject;
use ReflectionProperty;

/**
 * Class SQL_Base
 * @property bool HasChanges
 * @property array Changes
 * @property IChangeLogHistory history
 */
class SQL_Base implements JsonSerializable
{
    protected static ?bool $_use_change_log = null;
    protected array $props = [];
    protected static string $table;
    protected static string $database;

    protected array $_change_log = [];
    protected ?array $_history = null;
    protected ?bool $_from_db = null;

    public ?bool $HasChanges = false;

    public static bool $UseLog = false;
    public static array $Log = [];

    /**
     * Runs a query and groups the rows by the value of one column, so each key
     * holds a list of the rows (or mapped values) that share it. Replaces the
     * old Query($sql, $params, $return_type, $group_by) argument.
     *
     * @param string $sql
     * @param array|null $params
     * @param string $group_by column in the result set to group on
     * @param bool $objects_only map each row to an instance of the calling class
     * @param callable|null $map_function map each row with this instead
     * @return array<string|int, array>
     */
    public static function QueryGroup(
        string    $sql,
        ?array    $params,
        string    $group_by,
        bool      $objects_only = false,
        ?callable $map_function = null): array
    {
        if ($objects_only) {
            $return_type = static::class;
            $map_function = function (array $row) use ($return_type) {
                $c = new $return_type();
                $c->FromRow($row);
                return $c;
            };
        }

        // the grouping key has to come from the raw row, before any mapping
        $res = static::Query($sql, $params, false, function (array $row) use ($group_by, $map_function) {
            if (!array_key_exists($group_by, $row)) {
                Exception('QuickDRY Error: ' . $group_by . ' is not a column in the query results');
            }
            return [$row[$group_by], $map_function ? $map_function($row) : $row];
        });

        if (isset($res['error'])) {
            Exception($res);
        }

        $list = [];
        foreach ($res as [$key, $value]) {
            $list[$key][] = $value;
        }

        return $list;
    }

    /**
     * @param string $property
     * @return mixed|null
     */
    public static function GetType(string $property): mixed
    {
        return static::$prop_definitions[$property]['type'] ?? null;
    }

    /**
     * @param $database_prefix
     * @param $table
     * @param $lowercase_table
     * @param $database_type_prefix
     * @param bool $use_database_type_prefix
     * @return string
     */
    public static function TableToClass($database_prefix, $table, $lowercase_table, $database_type_prefix, bool $use_database_type_prefix = true): string
    {
        if ($lowercase_table) {
            $table = strtolower($table);
        }

        $database_prefix = $use_database_type_prefix ? strtolower($database_prefix) : '';
        $t = explode('_', ($database_prefix !== '' ? $database_prefix . '_' : '') . $table);

        $type = '';
        foreach ($t as $w) {
            $type .= preg_replace('/[^a-z0-9]/i', '', ucfirst($w));
        }
        $type .= 'Class';
        if (is_numeric($type[0])) {
            $type = 'i' . $type;
        }
        return ($use_database_type_prefix && $database_type_prefix !== '' ? $database_type_prefix . '_' : '') . $type;
    }

    /**
     * @param $database_prefix
     * @param $table
     * @param $lowercase_table
     * @param $database_type_prefix
     * @param bool $use_database_type_prefix
     * @return string
     */
    public static function StoredProcToClass($database_prefix, $table, $lowercase_table, $database_type_prefix, bool $use_database_type_prefix = true): string
    {
        // note: we need to leave underscores in
        // underscores are valid PHP and a naming choice for developers
        if ($lowercase_table) {
            $table = strtolower($table);
        }

        $database_prefix = $use_database_type_prefix ? strtolower($database_prefix) : '';
        $t = explode('_', ($database_prefix !== '' ? $database_prefix . '_' : '') . $table);

        $type = [];
        foreach ($t as $w) {
            $type [] = preg_replace('/[^a-z0-9]/i', '', ucfirst($w));
        }
        $type = implode('_', $type);
        $type .= 'Class';
        if (is_numeric($type[0])) {
            $type = 'i' . $type;
        }
        return ($use_database_type_prefix && $database_type_prefix !== '' ? $database_type_prefix . '_' : '') . $type;
    }

    /**
     * @param string $table
     * @param bool $lowercase_table
     * @return string
     */
    public static function TableToNiceName(string $table, bool $lowercase_table): string
    {
        if ($lowercase_table) {
            $table = strtolower($table);
        }

        $t = explode('_', $table);

        $type = '';
        foreach ($t as $w) {
            $type .= preg_replace('/[^a-z0-9]/i', '', ucfirst($w));
        }

        if (is_numeric($type[0])) {
            $type = 'i' . $type;
        }
        return $type;
    }

    /**
     *
     */
    public function __construct()
    {
        $this->props = self::GetVars();
    }

    /**
     * @param string $column_name
     * @return string
     */
    private static function ColumnNameToNiceName(string $column_name): string
    {
        return $column_name;
    }

    /**
     * @return bool
     */
    public function HasChangeLog(): bool
    {
        if (defined('DISABLE_CHANGE_LOG') && DISABLE_CHANGE_LOG) {
            return false;
        }
        if (strcasecmp(static::$table, 'change_log') == 0) { // don't change log the change log
            return false;
        }
        if (strcasecmp(static::$table, 'changelog') == 0) { // don't change log the change log
            return false;
        }

        if (!sizeof($this->_change_log)) { // don't log when nothing changed
            return false;
        }

        return !isset(static::$_use_change_log) || static::$_use_change_log;
    }

    /**
     * @param $var
     */
    public function Clear($var): void
    {
        $this->{'_' . $var} = null;
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset(static::$prop_definitions[$name]);
    }

    /**
     * @param $c
     * @throws ReflectionException
     */
    public static function GenProps($c): void
    {
        $o = new $c();
        $object = new ReflectionObject($o);
        $method = $object->getMethod('__get');
        $declaringClass = $method->getDeclaringClass();
        $filename = $declaringClass->getFileName();

        $props = [];

        $fp = fopen($filename, 'r');
        $code = fread($fp, filesize($filename));
        fclose($fp);
        $orig_code = $code;
        $pattern = '/@property\s+(.*?)[\r\n]/si';
        $matches = [];
        preg_match_all($pattern, $code, $matches);

        foreach ($matches[1] as $var) {
            $parts = explode(' ', $var);
            $props[$parts[sizeof($parts) - 1]] = trim(str_replace($parts[sizeof($parts) - 1], '', $var));
        }


        $pattern = '/public function __get\(\$name\)(.*?)\n\t}/si';
        $matches = [];
        preg_match($pattern, $code, $matches);
        if (isset($matches[1])) {
            $code = $matches[1];
            $pattern = '/case \'(.*?)\':/si';
            $matches = [];
            preg_match_all($pattern, $code, $matches);
            if (isset($matches[1])) {
                $code = $matches[1];
                foreach ($code as $get) {
                    if (!isset($props[$get]))
                        $props[$get] = 'undefined';
                }
            }
        }
        ksort($props);
        $php = '<?php

/**
 * @author Ben Kucenski
 * QuickDRY Framework ' . date('Y') . '
 *
';
        foreach ($props as $var => $type)
            $php .= ' * @property ' . $type . ' ' . $var . "\r\n";

        $php .= ' */';
        $code = preg_replace('/\<\?php\s+\/\*\*.*?\*\//si', '', $orig_code);
        $code = $php . $code;

        $fp = fopen($filename, 'w');
        fwrite($fp, $code);
        fclose($fp);
    }

    /**
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function __get(string $name)
    {
        return $this->GetProperty($name);
    }

    /**
     * @return array
     */
    public function Changes(): array
    {
        return $this->_change_log;
    }

    /**
     * @return IChangeLogHistory|null
     */
    public function History(): ?array
    {
        if (is_null($this->_history)) {
            $this->_history = $this->_history();
        }
        return $this->_history;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return mixed
     * @throws Exception
     */
    public function __set(string $name, mixed $value)
    {
        $this->SetProperty($name, $value);
        return $value;
    }

    /**
     * @return IChangeLogHistory|null
     */
    private function _history(): ?IChangeLogHistory
    {
        return ChangeLogHistory::GetHistory($this);
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public static function check_props($key): bool
    {
        return isset(static::$prop_definitions[$key]);
    }

    /**
     * @return array
     */
    public static function GetColumns(): array
    {
        $cols = [];
        foreach (static::$prop_definitions as $name => $def) {
            $cols[] = $name;
        }
        return $cols;
    }

    /**
     * @param array|null $where
     *
     * @return mixed
     */
    public static function Get(?array $where = null): mixed
    {
        return static::_Get($where);
    }

    /**
     * @param array|null $where
     * @param array|null $order_by
     * @param int|null $limit
     *
     * @return array|null
     */
    public static function GetAll(?array $where = null, ?array $order_by = null, ?int $limit = null): ?array
    {
        if (!is_null($order_by) && !is_array($order_by)) {
            Exception('QuickDRY Error: GetAll $order_by must be an assoc array ["col"=>"asc,desc",...]', true);
        }

        if (!is_null($where) && !is_array($where)) {
            Exception('QuickDRY Error: GetAll $where must be an assoc array ["col"=>"val",...]', true);
        }

        if (!is_null($order_by)) {
            foreach ($order_by as $col => $dir) {
                if (!self::check_props(trim($col))) {
                    Exception('QuickDRY Error: ' . $col . ' is not a valid order by column for ' . get_called_class());
                }
            }
        }

        if (is_array($where) && sizeof($where) == 0) {
            $where = null;
        }

        if (!is_null($where)) {
            foreach ($where as $col => $dir) {
                $col = str_replace('+', '', $col);
                if (!self::check_props(trim($col))) {
                    Exception('QuickDRY Error: ' . $col . ' is not a valid where column for ' . get_called_class());
                }
            }
        }

        return static::_GetAll($where, $order_by, $limit);
    }

    /**
     * @param array|null $where
     *
     * @return int
     */
    public static function GetCount(?array $where = null): int
    {
        return static::_GetCount($where);
    }

    /**
     * @param array|null $where
     * @param array|null $order_by
     * @param int $page
     * @param int $per_page
     * @param array|null $left_join
     * @param int|null $limit
     *
     * @return array|null
     */
    public static function GetAllPaginated(
        ?array $where = null,
        ?array $order_by = null,
        int    $page = 0,
        int    $per_page = 0,
        ?array $left_join = null,
        ?int   $limit = null
    ): ?array
    {
        return static::_GetAllPaginated($where, $order_by, $page, $per_page, $left_join, $limit);
    }

    /**
     * @return array
     */
    public static function GetVars(): array
    {
        return array_map(function () {
            return null;
        }, static::$prop_definitions);
    }

    /**
     * @param string|null $name
     * @return mixed|null
     * @throws Exception
     */
    protected function GetProperty(?string $name = null): mixed
    {
        if (array_key_exists($name, $this->props)) {
            return static::StrongType($name, $this->props[$name]);
        }
        Exception($name . ' is not a property of ' . get_class($this) . "\r\n");
    }

    /**
     * @return void
     */
    public function ClearProps(): void
    {
        foreach ($this->props as $n => $v) {
            $this->props[$n] = null;
        }
    }

    /**
     * @param array $arr
     * @param bool $null_string
     * @param array|null $prop_definitions
     * @return array
     * @throws Exception
     */
    public static function _ToArray(array $arr, bool $null_string = false, ?array $prop_definitions = null): array
    {
        // Cleans up an array of values so that it can ben
        // put into a database object and be saved into the database
        foreach ($arr as $k => $v) {
            if (is_object($v) && get_class($v) === 'DateTime') {
                $arr[$k] = isset($prop_definitions[$k]['type']) && strcasecmp($prop_definitions[$k]['type'], 'date') == 0 ? Dates::Datestamp($v) : Dates::Timestamp($v);
            } else {
                $arr[$k] = static::StrongType($k, $v);
            }
            if ($null_string && is_null($v)) {
                $arr[$k] = 'null';
            }
        }
        return $arr;
    }


    /**
     * @return array
     * @throws Exception
     */
    public function toArray(): array
    {
        return self::_ToArray($this->props, true, static::$prop_definitions);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function ToJSONArray(): array
    {
        return self::_ToArray($this->props, false, static::$prop_definitions);
    }

    /**
     * @param $name
     * @param $value
     * @return mixed|string|null
     * @throws Exception
     */
    protected function SetProperty($name, $value): mixed
    {
        if (!array_key_exists($name, $this->props)) {
            Exception('QuickDRY Error: ' . $name . ' is not a property of ' . get_class($this) . "\r\n");
        }

        if (is_array($value)) {
            Exception(['QuickDRY Error: Value assigned to property cannot be an array.', $value]);
        }

        if (is_object($value)) {
            if ($value instanceof DateTime) {
                $value = Dates::Timestamp($value);
            } else {
                Exception(['QuickDRY Error: Value assigned to property cannot be an object.', $value]);
            }
        }

        if ($value && strcasecmp((string)$value, 'null') == 0) {
            $value = null;
        }

        $old_val = static::StrongType($name, $this->props[$name]);
        $new_val = static::StrongType($name, $value);

        if ($old_val instanceof DateTime) {
            $old_val = Dates::Timestamp($old_val);
        }

        if ($new_val instanceof DateTime) {
            $new_val = Dates::Timestamp($new_val);
        }

        $changed = false;
        $change_reason = '';
        if (is_null($old_val) && !is_null($new_val)) {
            $changed = true;
            $change_reason = 'old = null, new not null';
        } elseif (!is_null($old_val) && is_null($new_val)) {
            $changed = true;
            $change_reason = 'old not null, new null';
        } elseif (!is_null($old_val) && !is_null($new_val) && strlen((string)$old_val) != strlen((string)$new_val)) {
            $changed = true;
            $change_reason = '"' . $new_val . '" "' . $old_val . '" ' . strlen((string)$new_val) . ' ' . strlen((string)$old_val) . ': strcmp = ' . strcmp((string)$new_val, (string)$old_val);
        } elseif (is_numeric($old_val) && is_numeric($new_val)) {

            if (abs($new_val - $old_val) > 0.000000001) {
                /**
                 * [new] => 5270.6709775679 -- PHP thinks these two numbers are different, so we need to compare to a very small number, not equal
                 * [old] => 5270.6709775679
                 * // from PHP's manual "never trust floating number results to the last digit, and do not compare floating point numbers directly for equality" - https://www.php.net/manual/en/language.types.float.php
                 */
                $changed = true;
                $change_reason = 'diff = ' . abs($new_val - $old_val);
            }
        } elseif (!is_null($old_val) && !is_null($new_val) && strcmp((string)$new_val, (string)$old_val) != 0) {
            $changed = true;
            $change_reason = '"' . $new_val . '" "' . $old_val . '" ' . strlen((string)$new_val) . ' ' . strlen((string)$old_val) . ': strcmp = ' . strcmp((string)$new_val, (string)$old_val);
        }


        if ($changed) {
            if (is_null($new_val)) {
                $new_val = 'null';
            }
            if (is_null($old_val)) {
                $old_val = 'null';
            }
            $this->_change_log[$name] = ['new' => $new_val, 'old' => $old_val, 'reason' => $change_reason];
            $this->HasChanges = true;
        }

        $this->props[$name] = $value;
        return $value;
    }

    /**
     * @param string|null $sort_by
     * @param string|null $dir
     * @param bool $modify
     * @param array|null $add
     * @param array|null $ignore
     * @param string|null $add_params
     * @param bool $sortable
     * @param array|null $column_order
     *
     * @return string
     */
    public static function GetHeader(
        ?string $sort_by = '',
        ?string $dir = '',
        ?bool   $modify = false,
        ?array  $add = [],
        ?array  $ignore = [],
        ?string $add_params = '',
        ?bool   $sortable = true,
        ?array  $column_order = []
    ): string
    {
        return static::_GetHeader(static::$prop_definitions, $sort_by, $dir, $modify, $add, $ignore, $add_params, $sortable, $column_order);
    }

    /**
     * @param bool $modify
     * @param array $add
     * @param array $ignore
     * @param bool $sortable
     * @param array $column_order
     *
     * @return string
     */
    public static function GetBareHeader(
        bool  $modify = false,
        array $add = [],
        array $ignore = [],
        bool  $sortable = true,
        array $column_order = []): string
    {
        return static::_GetBareHeader(static::$prop_definitions, $modify, $add, $ignore, $sortable, $column_order);
    }

    /**
     * @param array $props
     * @param string|null $sort_by
     * @param string|null $dir
     * @param bool $modify
     * @param array|null $add
     * @param array|null $ignore
     * @param string|null $add_params
     * @param bool $sortable
     * @param array|null $column_order
     *
     * @return string
     */
    protected static function _GetHeader(
        array   $props,
        ?string $sort_by,
        ?string $dir,
        ?bool   $modify = false,
        ?array  $add = [],
        ?array  $ignore = [],
        ?string $add_params = '',
        ?bool   $sortable = true,
        ?array  $column_order = []
    ): string
    {
        $not_dir = $dir == 'asc' ? 'desc' : 'asc';
        $arrow = $dir == 'asc' ? '&uarr;' : '&darr;';

        $res = '';

        $columns = [];
        if (!$add) {
            $add = [];
        }
        if (!$ignore) {
            $ignore = [];
        }

        foreach ($props as $name => $info) {
            if (!in_array($name, $ignore)) {
                if ($sortable) {
                    $columns[$name] = '<th><a href="' . CURRENT_PAGE . '?sort_by=' . $name . '&sort_dir=' . (strcasecmp($sort_by ?? '', $name ?? '') == 0 ? $not_dir : 'asc') . '&per_page=' . PER_PAGE . '&' . $add_params . '">' . static::ColumnNameToNiceName($name) . '</a>' . (strcasecmp($sort_by ?? '', $name ?? '') == 0 ? ' ' . $arrow : '') . '</th>';
                } else {
                    $columns[$name] = '<th>' . static::ColumnNameToNiceName($name) . '</th>';
                }
            }
        }

        if (sizeof($add) > 0) {
            foreach ($add as $header => $value) {
                if (is_array($value) && $sortable) {
                    $columns[$value['value']] = '<th><a href="' . CURRENT_PAGE . '?sort_by=' . $value['sort_by'] . '&dir=' . ($sort_by == $value['sort_by'] ? $not_dir : 'asc') . '&per_page=' . PER_PAGE . '&' . $add_params . '">' . $header . '</a>' . ($sort_by == $value['sort_by'] ? ' ' . $arrow : '') . '</th>';
                } elseif (is_array($value)) {
                    $columns[$value['value']] = '<th>' . $header . '</th>';
                } else {
                    $columns[$value] = '<th>' . $header . '</th>';
                }
            }
        }

        if (sizeof($column_order) > 0) {
            foreach ($column_order as $order)
                $res .= $columns[$order];
        } else
            $res = '<thead><tr>' . implode('', $columns);

        if ($modify)
            $res .= '<th>Action</th>';

        return $res . '</tr></thead>';
    }

    /**
     * @param array $props
     * @param bool $modify
     * @param array $add
     * @param array $ignore
     * @param bool $sortable
     * @param array $column_order
     *
     * @return string
     */
    protected static function _GetBareHeader(array $props, bool $modify = false, array $add = [], array $ignore = [], bool $sortable = true, array $column_order = []): string
    {
        $res = '';
        $columns = [];

        foreach ($props as $name => $info)
            if (!in_array($name, $ignore))
                if ($sortable)
                    $columns[$name] = '<th>' . $info['display'] . '</th>' . "\r\n";
                else
                    $columns[$name] = '<th>' . $info['display'] . '</th>' . "\r\n";

        if (sizeof($add) > 0)
            foreach ($add as $header => $value) {
                if (is_array($value) && $sortable) {
                    $columns[$value['value']] = '<th>' . $header . '</th>' . "\r\n";
                } elseif (is_array($value)) {
                    $columns[$value['value']] = '<th>' . $header . '</th>' . "\r\n";
                } else {
                    $columns[$value] = '<th>' . $header . '</th>' . "\r\n";
                }

            }

        if (sizeof($column_order) > 0) {
            foreach ($column_order as $order)
                $res .= $columns[$order];
        } else
            $res = '<thead><tr>' . implode('', $columns);

        if ($modify)
            $res .= '<th>Action</th>' . "\r\n";

        return $res . '</tr></thead>';
    }

    /**
     * @param string $column_name
     * @param mixed $value
     * @param bool $force_value
     * @return mixed|null
     */
    public function ValueToNiceValue(string $column_name, mixed $value = null, bool $force_value = false): mixed
    {
        return $value;
    }

    /**
     * @param bool $modify
     * @param array|null $swap
     * @param array|null $add
     * @param array|null $ignore
     * @param array|null $custom_link
     * @param array|null $column_order
     *
     * @return string
     */
    public function ToRow(
        bool   $modify = false,
        ?array $swap = null,
        ?array $add = null,
        ?array $ignore = null,
        ?array $custom_link = null,
        ?array $column_order = null
    ): string
    {
        $res = '';

        $columns = [];
        if (is_null($swap)) {
            $swap = [];
        }

        if (is_null($add)) {
            $add = [];
        }

        if (is_null($ignore)) {
            $ignore = [];
        }

        foreach ($this->props as $name => $value) {
            if (in_array($name, $ignore)) {
                continue;
            }


            if (array_key_exists($name, $swap)) {
                if (method_exists($this, $swap[$name])) {
                    $value = $this->{$swap[$name]}();
                } else {
                    $value = $this->{$swap[$name]};
                }
            } else {
                $value = $this->ValueToNiceValue($name, $this->$name);
            }


            if (!is_object($value)) {
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
                $columns[$name] = '<td>' . $value . '</td>';
            } elseif ($value instanceof DateTime) {
                $columns[$name] = '<td>' . Dates::Timestamp($value) . '</td>';
            } else {
                $columns[$name] = '<td><i>Object: </i>' . get_class($value) . '</td>';
            }
        }

        if (sizeof($add) > 0)
            foreach ($add as $value) {
                if (is_array($value)) {
                    $name = $value['value'];
                    $value = $this->{$value['value']};
                } else {
                    $name = $value;
                    $value = $this->$value;
                }
                if (is_array($value)) {
                    $value = implode(',', $value);
                }

                $columns[$name] = '<td>' . $value . '</td>';
            }
        if ($column_order && sizeof($column_order) > 0) {
            foreach ($column_order as $name) {
                $res .= $columns[$name];
            }
        } else {
            $res = '<tr>' . implode('', $columns);
        }

        if ($modify) {
            $res .= '
          <td class="data_text">
             <a href="#"  onclick="' . get_base_class($this) . '.Load(' . $this->{static::$_primary[0]} . ')"><i class="fa fa-edit"></i></a>
          ';
            if (is_array($custom_link)) {
                $res .= '<a class="btn btn-primary btn-sm" href="' . $custom_link['page'] . '?' . static::$_primary[0] . '=' . $this->{static::$_primary[0]} . '">' . $custom_link['title'] . '</a>';
            }
            $res .= '</td>';
        }
        return $res . '</tr>';
    }

    // $trigger_change_log true is for FORM data to pass changes through set_property to trigger change log.
    // when coming from database, don't trigger change log
    // strict will halt when the hash passed in contains columns not in the table definition
    /**
     * @param array $row
     * @param bool $trigger_change_log
     * @param bool $strict
     */
    public function FromRow(
        array $row,
        bool  $trigger_change_log = false,
        bool  $strict = false): void
    {
        if (is_null($trigger_change_log)) {
            $trigger_change_log = false;
        }
        if (is_null($strict)) {
            $strict = false;
        }

        $this->_from_db = true;
        $missing = [];

        foreach ($row as $name => $value) {
            if (property_exists(static::class, $name)) {
                $reflection = new ReflectionProperty(static::class, $name);

                // check if the property has a type declaration
                $type = $reflection->getType();
                if ($type !== null) {
                    $typeName = $type->getName();

                    switch ($typeName) {
                        case 'int':
                            $value = (int)$value;
                            break;
                        case 'float':
                            $value = (float)$value;
                            break;
                        case 'bool':
                            $value = (bool)$value;
                            break;
                        case 'string':
                            $value = (string)$value;
                            break;
                        case 'array':
                            $value = (array)$value;
                            break;
                        default:
                            // if it's a class/interface type, you may need to handle
                            // conversion (e.g., json_decode to object, or construct new instance)
                            if (!$value instanceof $typeName) {
                                if (is_array($value) && class_exists($typeName)) {
                                    // optionally: hydrate object from array
                                    $value = new $typeName(...$value);
                                }
                            }
                            break;
                    }
                }

                $this->$name = $value;
                continue;
            }

            if (!isset(static::$prop_definitions[$name])) {
                if ($strict) {
                    $missing[$name] = $value;
                }
                continue;
            }

            if ($trigger_change_log) {
                $this->$name = $value;
            } else {
                $this->props[$name] = isset($row[$name]) ? $value : (null);
            }
        }

        if ($strict && sizeof($missing)) {
            Exception([
                'error'   => 'QuickDRY Error: Missing Columns',
                'Object'  => get_class($this),
                'Columns' => $missing,
                'Values'  => $row,
            ]);
        }
    }

    /**
     * @return QueryExecuteResult
     */
    public function Save(): QueryExecuteResult
    {
        return new QueryExecuteResult();
    }

    /**
     * @param array $req
     * @param bool $save
     * @param bool $keep_existing_values
     * @return QueryExecuteResult
     */
    public function FromRequest(array $req, bool $save = true, bool $keep_existing_values = true): QueryExecuteResult
    {
        foreach ($this->props as $name => $value) {
            $this->$name = array_key_exists($name, $req) ? $req[$name] : (!$keep_existing_values ? null : $this->props[$name]);
        }

        if ($save) {
            return $this->Save();
        }
        return new QueryExecuteResult();
    }

    /**
     * @param self $item
     * @return array
     */
    public static function getHeaders(self $item): array
    {
        $cols = array_keys(get_object_vars($item));
        foreach ($cols as $i => $col) {
            if ($col[0] === '_') {
                unset($cols[$i]);
            }
        }
        return $cols;
    }

    /**
     * @param self[] $items
     * @return SimpleExcel|null
     * @throws Exception
     */
    public static function toExcel(array $items): ?SimpleExcel
    {
        if (!sizeof($items)) {
            return null;
        }
        $cols = array_keys($items[0]->toArray());

        $se = new SimpleExcel();
        $se->Report = $items;
        $se->Title = static::class;
        $se->Columns = [];
        foreach ($cols as $col) {
            $se->Columns[$col] = new SimpleExcel_Column($col, $col);
        }

        return $se;
    }

    /**
     * @return string
     */
    public function PrimaryKey(): string
    {
        $key = [];
        foreach (static::$_primary as $col) {
            $key [] = $col . ':' . $this->$col;
        }
        return implode('::', $key);
    }

    // occasionally we end up reusing a selection over and over on the same page
    protected static array $_select_cache = [];

    /**
     * @param        $selected
     * @param ElementID $id
     * @param        $value
     * @param        $order_by
     * @param string $display
     * @param array|null $where
     * @param bool $show_none
     * @param string $onchange
     * @return string
     */
    protected static function _Select(
        $selected,
        ElementID $id,
        $value,
        $order_by,
        string $display = '',
        ?array $where = null,
        bool $show_none = true,
        string $onchange = ''): string
    {
        if (is_null($show_none)) {
            $show_none = true;
        }

        $type = get_called_class();


        $hash = md5(serialize([$type, $order_by, $where]));
        if (!isset(static::$_select_cache[$hash])) {
            if (!method_exists($type, 'GetAll')) {
                exit($type . '::GetAll');
            }

            $items = $type::GetAll($where, is_array($order_by) ? $order_by : [$order_by => 'asc']);
            static::$_select_cache[$hash] = $items;
        } else
            $items = static::$_select_cache[$hash];

        return self::_SelectItems($items, $selected, $id, $value, $order_by, $display, $show_none, $onchange);
    }

    /**
     * @param        $items
     * @param        $selected
     * @param ElementID $id
     * @param        $value
     * @param        $order_by
     * @param string $display
     * @param bool $show_none
     * @param string $onchange
     * @return string
     */
    protected static function _SelectItems(
        $items,
        $selected,
        ElementID $id,
        $value,
        $order_by,
        string $display = '',
        bool $show_none = true,
        string $onchange = ''): string
    {
        if (is_null($show_none)) {
            $show_none = true;
        }

        $name = $id->name;
        $id = $id->id;

        if ($display == '') {
            if (is_array($order_by)) {
                $display = array_keys($order_by)[0];
            } else {
                $display = $order_by;
            }
        }

        $select = '';

        if (is_array($selected)) {
            $select .= '<select class="form-control" onchange="' . $onchange . '" multiple size="' . (min(sizeof($items) + 1, 10)) . '" id="' . $id . '" name="' . $name . '[]">';
        } else {
            $select .= '<select class="form-control" onchange="' . $onchange . '"  id="' . $id . '"name="' . $name . '">';
        }

        if ($show_none) {
            $select .= '<option value="null">' . (is_bool($show_none) ? 'None' : $show_none) . '</option>' . "\r\n";
        }

        if (sizeof($items) > 0) {
            if (is_array($selected)) {
                foreach ($items as $item)
                    if (in_array($item->$value, $selected))
                        $select .= '<option selected="selected" value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
                    else
                        $select .= '<option value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
            } else {
                foreach ($items as $item)
                    if ($selected != $item->$value) // needs to be a loose comparison otherwise it doesn't work with numbers
                        $select .= '<option value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
                    else
                        $select .= '<option selected="selected" value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
            }
        }
        $select .= '</select>';
        return $select;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function jsonSerialize(): array
    {
        return $this->ToJSONArray();
    }

    /**
     * @param        $selected
     * @param        $id
     * @param        $value
     * @param        $order_by
     * @param string $display
     * @param array|null $where
     *
     * @return string
     */
    protected static function _EasySelect(
        $selected,
        $id,
        $value,
        $order_by,
        string $display = '',
        ?array $where = null): string
    {
        $type = static::class;


        if (!method_exists($type, 'GetAll')) {
            exit("$type::GetAll");
        }
        $items = $type::GetAll($where, [$order_by => 'asc']);

        return self::_EasySelectItems($items, $selected, $id, $value, $order_by, $display);
    }

    /**
     * @param        $items
     * @param        $selected
     * @param        $id
     * @param        $value
     * @param        $order_by
     * @param string $display
     *
     * @return string
     */
    protected static function _EasySelectItems($items, $selected, $id, $value, $order_by, string $display = ''): string
    {
        if ($display == '')
            $display = $order_by;

        $select_remove = '<select multiple size="10" id="remove_' . $id . '[]" name="remove_' . $id . '[]"><option value="">None</option>';
        $select_add = '<select multiple size="10" id="add_' . $id . '[]" name="add_' . $id . '[]"><option value="">None</option>';

        if (sizeof($items) > 0) {
            if (is_array($selected)) {
                foreach ($items as $item)
                    if (in_array($item->$value, $selected))
                        $select_remove .= '<option value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
                    else
                        $select_add .= '<option value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
            } else {
                foreach ($items as $item)
                    if ($selected != $item->$value)
                        $select_add .= '<option value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
                    else
                        $select_remove .= '<option value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
            }
        }

        $select_remove .= '</select>';
        $select_add .= '</select>';

        return '<table><tr><td style="vertical-align: top"><b>Current Items</b><br/>(select to remove)<br/>' . $select_remove . '</td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td style="vertical-align: top;"><b>Available Items</b><br/>(select to add)<br/>' . $select_add . '</td></tr></table>';
    }

    /**
     * @param        $selected
     * @param        $id
     * @param        $value
     * @param array|null $order_by
     * @param string $display
     * @param array|null $where
     *
     * @return string
     */
    protected static function _JQuerySelect(
        $selected,
        $id,
        $value,
        ?array $order_by = null,
        string $display = '',
        ?array $where = null): string
    {
        $type = static::class;
        if (!method_exists($type, 'GetAll')) {
            exit($type . '::GetAll');
        }

        $items = $type::GetAll($where, $order_by);

        return self::_JQuerySelectItems($items, $selected, $id, $value, $display);

    }

    /**
     * @param        $items
     * @param        $selected
     * @param        $id
     * @param        $value
     * @param string $display
     *
     * @return string
     */
    protected static function _JQuerySelectItems($items, $selected, $id, $value, string $display = ''): string
    {
        // requires drag_select.js

        if ($display == '')
            $display = $value;


        $remove = [];
        $add = [];

        if (sizeof($items) > 0) {
            if (is_array($selected)) {
                foreach ($items as $item)
                    if (in_array($item->$value, $selected))
                        $remove[$item->$value] = $item->$display;
                    else
                        $add[$item->$value] = $item->$display;
            } else {
                foreach ($items as $item)
                    if ($selected != $item->$value)
                        $add[$item->$value] = $item->$display;
                    else
                        $remove[$item->$value] = $item->$display;
            }
        }
        $not_selected = [];
        foreach ($add as $cid => $val)
            $not_selected[] = $cid;

        $html = '
		<div class="dds_panel">
<h2>Selected Items</h2>
<p>Select:
    <a href=\'#\' onclick=\'return $.dds.selectAll("list_' . $id . '_1");\'>all</a>
    <a href=\'#\' onclick=\'return $.dds.selectNone("list_' . $id . '_1");\'>none</a>
    <a href=\'#\' onclick=\'return $.dds.selectInvert("list_' . $id . '_1");\'>invert</a>
</p>

<ul class="dds" id="list_' . $id . '_1">
		';
        foreach ($remove as $item_id => $name)
            $html .= '<li class="dds" id="list_' . $id . '_1_item_' . $item_id . '">' . $name . '</li>';

        $html .= '
</ul>
<input type="hidden" name="modify_' . $id . '" value="1" />
<input type="hidden" name="' . $id . '" id=\'list_' . $id . '_1_serialised\' value="' . implode(',', $selected) . '" />
</div>
';

        $html .= '
		<div class="dds_panel">
<h2>Available Items</h2>
<p>Select:
    <a href=\'#\' onclick=\'return $.dds.selectAll("list_' . $id . '_2");\'>all</a>
    <a href=\'#\' onclick=\'return $.dds.selectNone("list_' . $id . '_2");\'>none</a>
    <a href=\'#\' onclick=\'return $.dds.selectInvert("list_' . $id . '_2");\'>invert</a>
</p>

<ul class="dds" id="list_' . $id . '_2">
		';
        foreach ($add as $item_id => $name)
            $html .= '<li class="dds" id="list_' . $id . '_2_item_' . $item_id . '">' . $name . '</li>';

        $html .= '
</ul>
<input type="hidden" name="' . $id . '_remaining" id=\'list_' . $id . '_2_serialised\' value="' . implode(',', $not_selected) . '" />
</div>
';

        $html .= '<div style="clear: both;"></div>';

        return $html;

    }

    /**
     * @param array $items
     * @param array|null $selected
     * @param ElementID $id
     * @param string $value
     * @param string $display
     * @param bool $show_none
     * @return string
     */
    protected static function _SelectMultiItems(
        array     $items,
        ?array    $selected,
        ElementID $id,
        string    $value,
        string    $display = '',
        bool      $show_none = true): string
    {
        if (sizeof($items) == 1) {
            $selected = [$items[0]->$value];
        }

        $select = '<select class="form-control" multiple size="' . (min(sizeof($items) + 1, 10)) . '" id="' . $id->id . '" name="' . $id->name . '[]">';

        if ($show_none)
            $select .= '<option value="null">None</option>' . "\r\n";

        if (sizeof($items) > 0) {
            foreach ($items as $item)
                if (in_array($item->$value, $selected))
                    $select .= '<option selected="selected" value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
                else
                    $select .= '<option value="' . $item->$value . '">' . $item->$display . '</option>\r\n';
        }
        $select .= '</select>';
        return $select;
    }

    /**
     * @param $selected
     * @param $id
     * @param $value
     * @param $order_by
     * @param string $display
     * @param array|null $where
     * @return string
     */
    protected static function _EasySelectCheckbox($selected, $id, $value, $order_by, string $display = '', ?array $where = null): string
    {
        $type = static::class;

        if (!method_exists($type, 'GetAll')) {
            exit("$type::GetAll");
        }
        $items = $type::GetAll($where, [$order_by => 'asc']);

        return self::_EasySelectCheckboxItems($items, $selected, $id, $value, $order_by, $display);
    }

    /**
     * @param $items
     * @param $selected
     * @param $id
     * @param $value
     * @param $order_by
     * @param string $display
     * @return string
     */
    protected static function _EasySelectCheckboxItems($items, $selected, $id, $value, $order_by, string $display = ''): string
    {
        if ($display == '') {
            $display = $order_by;
        }

        $select_remove = '<div style="min-width: 400px; height: 500px; overflow-y: auto;">';
        $select_add = '<div style="min-width: 400px; height: 500px; overflow-y: auto;">';

        if (sizeof($items) > 0) {
            if (is_array($selected)) {
                foreach ($items as $item) {
                    if (in_array($item->$value, $selected)) {
                        $select_remove .= '<input type="checkbox" name="remove_' . $id . '[]" value="' . $item->$value . '">' . $item->$display . '<br/>';
                    } else {
                        $select_add .= '<input type="checkbox" name="add_' . $id . '[]" value="' . $item->$value . '">' . $item->$display . '<br/>';
                    }
                }
            } else {
                foreach ($items as $item) {
                    if ($selected != $item->$value) {
                        $select_add .= '<input type="checkbox" name="add_' . $id . '[]" value="' . $item->$value . '">' . $item->$display . '<br/>';
                    } else {
                        $select_remove .= '<input type="checkbox" name="remove_' . $id . '[]" value="' . $item->$value . '">' . $item->$display . '<br/>';
                    }
                }
            }
        }

        $select_remove .= '</div>';
        $select_add .= '</div>';

        return '<table><tr><td style="vertical-align: top"><b>Current Items</b><br/>(select to remove)<br/>' . $select_remove . '</td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td style="vertical-align: top"><b>Available Items</b><br/>(select to add)<br/>' . $select_add . '</td></tr></table>';

    }

}
