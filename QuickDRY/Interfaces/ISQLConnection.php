<?php
declare(strict_types=1);

namespace QuickDRY\Interfaces;

use QuickDRY\Connectors\QueryExecuteResult;

/**
 *
 */
interface ISQLConnection
{
    /**
     * @param string $host
     * @param string $user
     * @param string $pass
     */
    public function __construct(string $host, string $user, string $pass);

    /**
     * @param string $database
     * @param string $table
     * @return string
     */
    public function TableToClass(string $database, string $table): string;

    /**
     * @param mixed $db_base
     * @return bool
     */
    public function CheckDatabase(mixed $db_base): bool;

    /**
     * @param mixed $db_base
     * @return void
     */
    public function SetDatabase(mixed $db_base): void;

    /**
     * Runs a query and returns either:
     *  - array like ['error' => ..., 'data' => ..., ...] when $map_function is null
     *  - or mapped rows (mixed) when $map_function is provided
     */
    public function Query(mixed $sql, ?array $params = null, mixed $map_function = null): mixed;

    /**
     * @param string $sql
     * @param array|null $params
     * @param bool $large
     * @return QueryExecuteResult
     */
    public function Execute(string $sql, ?array $params = null, bool $large = false): QueryExecuteResult;

    /**
     * @param string $query
     * @param bool $large
     * @return QueryExecuteResult|null
     */
    public function ExecuteWindows(string $query, bool $large = false): ?QueryExecuteResult;

    /**
     * @param int|null $LastID
     * @return int|null
     */
    public function LastID(?int $LastID = null): ?int;

    /**
     * @return array
     */
    public function GetDatabases(): array;

    /**
     * @return array
     */
    public function GetTables(): array;

    /**
     * @param string $table_name
     * @return array
     */
    public function GetTableColumns(string $table_name): array;

    /**
     * @param string $table_name
     * @return array
     */
    public function GetTableIndexes(string $table_name): array;

    /**
     * @param string $table_name
     * @return mixed
     */
    public function GetIndexes(string $table_name): mixed;

    /**
     * @param string $table_name
     * @return mixed
     */
    public function GetUniqueKeys(string $table_name): mixed;

    /**
     * @param string $table_name
     * @return mixed
     */
    public function GetForeignKeys(string $table_name): mixed;

    /**
     * @param string $table_name
     * @return mixed
     */
    public function GetLinkedTables(string $table_name): mixed;

    /**
     * @param string $table_name
     * @return array
     */
    public function GetPrimaryKey(string $table_name): array;

    /**
     * @return array
     */
    public function GetTriggers(): array;

    /**
     * @return array
     */
    public function GetDefinitions(): array;

    /**
     * @return array
     */
    public function GetStoredProcs(): array;

    /**
     * @param string $stored_proc
     * @return array
     */
    public function GetStoredProcParams(string $stored_proc): array;
}
