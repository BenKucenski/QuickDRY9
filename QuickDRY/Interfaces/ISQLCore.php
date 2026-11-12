<?php
declare(strict_types=1);

namespace QuickDRY\Interfaces;

use QuickDRY\Connectors\QueryExecuteResult;
use QuickDRY\Connectors\SQL_Query;
use QuickDRY\Web\ElementID;

/**
 *
 */
interface ISQLCore
{
    // ---- UI / display helpers ----
    /**
     * @param string|null $selected
     * @param ElementID|null $id
     * @return string
     */
    public static function Select(?string $selected = null, ?ElementID $id = null): string;

    /**
     * @param string $column_name
     * @return string
     */
    public static function ColumnNameToNiceName(string $column_name): string;

    /**
     * @param string $column_name
     * @param mixed|null $value
     * @param bool $force_value
     * @return mixed
     */
    public function ValueToNiceValue(string $column_name, mixed $value = null, bool $force_value = false): mixed;

    /**
     * @param string $column_name
     * @return bool
     */
    public static function IgnoreColumn(string $column_name): bool;

    // ---- persistence (instance) ----

    /**
     * @param bool $return_query
     * @return SQL_Query|QueryExecuteResult|null
     */
    public function Insert(bool $return_query = false): SQL_Query|QueryExecuteResult|null;

    /**
     * @param bool $return_query
     * @return SQL_Query|QueryExecuteResult|null
     */
    public function Update(bool $return_query = false): SQL_Query|QueryExecuteResult|null;

    /**
     * @return QueryExecuteResult
     */
    public function Remove(): QueryExecuteResult;

    /**
     * @return bool
     */
    public function CanDelete(): bool;

    // ---- misc business helper ----

    /**
     * @param mixed $search
     * @param ICurrentUser $user
     * @return array
     */
    public static function Suggest(mixed $search, ICurrentUser $user): array;

    // ---- schema / metadata ----

    /**
     * @return array
     */
    public static function GetTables(): array;

    /**
     * @return array
     */
    public static function GetDatabases(): array;

    /**
     * @param string $db_base
     * @return void
     */
    public static function SetDatabase(string $db_base): void;

    /**
     * @param string $table_name
     * @return array
     */
    public static function GetTableColumns(string $table_name): array;

    /**
     * @param string $table_name
     * @return array
     */
    public static function GetTableIndexes(string $table_name): array;

    /**
     * @param string $table_name
     * @return mixed
     */
    public static function GetUniqueKeys(string $table_name): mixed;

    /**
     * @param string $table_name
     * @return mixed
     */
    public static function GetIndexes(string $table_name): mixed;

    /**
     * @param string $table_name
     * @return mixed
     */
    public static function GetForeignKeys(string $table_name): mixed;

    /**
     * @param string $table_name
     * @return mixed
     */
    public static function GetLinkedTables(string $table_name): mixed;

    /**
     * @return array
     */
    public static function GetTriggers(): array;

    /**
     * @return array
     */
    public static function GetStoredProcs(): array;

    /**
     * @return array
     */
    public static function GetDefinitions(): array;

    /**
     * @param mixed $stored_proc
     * @return array
     */
    public static function GetStoredProcParams(mixed $stored_proc): array;

    /**
     * @param string $table_name
     * @return array
     */
    public static function GetPrimaryKey(string $table_name): array;

    // ---- query execution ----

    /**
     * @param string $sql
     * @param array|null $params
     * @param bool $large
     * @return QueryExecuteResult|null
     */
    public static function Execute(string $sql, ?array $params = null, bool $large = false): ?QueryExecuteResult;

    /**
     * @param string $sql
     * @param array|null $params
     * @param callable $map_function
     * @return mixed
     */
    public static function QueryMap(string $sql, ?array $params, callable $map_function): mixed;

    /**
     * @param string $sql
     * @param array|null $params
     * @param string $group_by
     * @param bool $objects_only
     * @param callable|null $map_function
     * @return array
     */
    public static function QueryGroup(string $sql, ?array $params, string $group_by, bool $objects_only = false, ?callable $map_function = null): array;

    /**
     * @param string $sql
     * @param array|null $params
     * @param bool $objects_only
     * @param callable|null $map_function
     * @return array
     */
    public static function Query(string $sql, ?array $params = null, bool $objects_only = false, ?callable $map_function = null): array;

    /**
     * @return string
     */
    public static function GUID(): string;

    /**
     * @return ISQLConnection|null
     */
    public static function getConnection(): ?ISQLConnection;

    /**
     * @param int|null $LastID
     * @return int|string|null
     */
    public static function LastID(?int $LastID = null): int|string|null;
}
