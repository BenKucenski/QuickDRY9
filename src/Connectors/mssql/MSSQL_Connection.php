<?php

namespace Bkucenski\Quickdry\Connectors\mssql;

use Exception;
use Bkucenski\Quickdry\Connectors\mssql;
use Bkucenski\Quickdry\Connectors\QueryExecuteResult;
use Bkucenski\Quickdry\Utilities\Debug;
use Bkucenski\Quickdry\Utilities\Helpers;
use Bkucenski\Quickdry\Utilities\Log;
use Bkucenski\Quickdry\Utilities\Metrics;
use Bkucenski\Quickdry\Utilities\strongType;


/**f
 * Class MSSQL_Connection
 */
class MSSQL_Connection extends strongType
{
    public static array $log = [];
    public static bool $use_log = false;
    public static bool $keep_files = false;
    public bool $IgnoreDuplicateError = false;
    public float $query_time = 0;
    public int $query_count = 0;

    private bool $_usesqlsrv = false;
    private array $_LastConnection;

    protected array $db_conns = [];

    /* @var mixed $db */
    protected $db = null;

    protected ?string $current_db = null;

    protected ?string $DB_HOST;
    protected ?string $DB_USER;
    protected ?string $DB_PASS;

    /**
     * @param string $host
     * @param string $user
     * @param string $pass
     */
    public function __construct(string $host, string $user, string $pass)
    {
        $this->DB_HOST = $host;
        $this->DB_USER = $user;
        $this->DB_PASS = $pass;
    }


    /**
     * @return void
     */
    private function _connect(): void
    {
        // p: means persistent
        if (!is_null($this->current_db)) {
            $this->SetDatabase($this->current_db);
        } else {
            $this->SetDatabase('');
        }
    }

    /**
     * @param string $database
     * @param string $table
     *
     * @return string
     */
    public function TableToClass(string $database, string $table): string
    {
        $t = explode('_', $database . '_' . $table);
        $type = '';
        foreach ($t as $w)
            $type .= preg_replace('/[^a-z0-9]/i', '', ucfirst($w));
        $type .= 'Class';
        if (is_numeric($type[0]))
            $type = 'i' . $type;
        return 'MS' . $type;
    }

    /**
     * @param $db_base
     *
     * @return bool
     */
    public function CheckDatabase($db_base): bool
    {
        $sql = 'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = "' . $db_base . '"';
        $res = $this->Query($sql);
        return isset($res['data'][0]['SCHEMA_NAME']);
    }

    /**
     * @param $db_base
     *
     * @return void
     */

    public function SetDatabase($db_base): void
    {
        $this->_LastConnection['database'] = $db_base;
        $this->_LastConnection['DB_HOST'] = $this->DB_HOST;
        $this->_LastConnection['UID'] = $this->DB_USER;
        $this->_LastConnection['error'] = '';
        $this->_LastConnection['reuse connection'] = '';
        $this->_LastConnection['_usesqlsrv'] = '';

        $time = microtime(true);
        $error = '';

        if (strcmp($this->current_db, $db_base) == 0 && $this->db) {
            $this->_LastConnection['reuse connection'] = $this->current_db . ' = ' . $db_base;
            return;
        }


        if (!$this->_usesqlsrv && Helpers::IsWindows() && function_exists('sqlsrv_connect')) {
            $this->_usesqlsrv = true;
            $this->_LastConnection['_usesqlsrv'] = 'true';
        }

        if (!isset($this->db_conns[$db_base]) || is_null($this->db_conns[$db_base])) {
            try {
                if (!$this->_usesqlsrv) {
                    if (!function_exists('mssql_connect')) {
                        exit('mssql_connect');
                    }
                    if (!function_exists('mssql_min_error_severity')) {
                        exit('mssql_min_error_severity');
                    }
                    if (!function_exists('mssql_get_last_message')) {
                        exit('mssql_get_last_message');
                    }
                    if (!function_exists('mssql_select_db')) {
                        exit('mssql_select_db');
                    }
                    $this->db_conns[$db_base] = mssql_connect($this->DB_HOST, $this->DB_USER, $this->DB_PASS);
                    if (!$this->db_conns[$db_base]) {
                        Debug::Halt(['Could not connect', $this->DB_HOST, $this->DB_USER]);
                    }
                    mssql_min_error_severity(1);
                    if ($db_base) {
                        $error = mssql_get_last_message();
                        if ($error) {
                            throw new Exception($error);
                        }
                        mssql_select_db($db_base, $this->db_conns[$db_base]);
                    }
                } else {
                    sqlsrv_errors(SQLSRV_ERR_ERRORS);
                    if (stristr($db_base, '.') !== false) { // linked server support
                        $this->db_conns[$db_base] = sqlsrv_connect($this->DB_HOST, ['UID' => $this->DB_USER, 'PWD' => $this->DB_PASS]);
                    } else {
                        $this->db_conns[$db_base] = sqlsrv_connect($this->DB_HOST, ['Database' => $db_base, 'UID' => $this->DB_USER, 'PWD' => $this->DB_PASS]);
                    }
                    $error = print_r(sqlsrv_errors(), true);
                    if (isset($error['message']) && $error['message']) {
                        throw new Exception($error);
                    }
                }
            } catch (Exception $e) {
                // Log exception
                Debug::Halt($e);
            }
        }

        $this->_LastConnection['error'] = $error;

        $this->db = $this->db_conns[$db_base];
        if (!$this->db) {
            Debug::Halt($this->_LastConnection);
        }
        $this->current_db = $db_base;
        $time = microtime(true) - $time;
        $this->query_time += $time;
        $sql = 'Set Database: ' . $db_base;
        self::Log($sql, null, $time, $error);

        $this->_LastConnection['current_db'] = $db_base;

        /**
         * if(!$this->_usesqlsrv) {
         * //mssql_query('SET ARITHABORT ON', $this->db); // https://msdn.microsoft.com/en-us/library/ms190306.aspx
         * }
         * **/
        /*
            // this query should show "1" for arithabort when run from SSMS
            select
                arithabort,
                *
            from
                sys.dm_exec_sessions
            where
                session_id > 50

         */
    }

    /**
     * @param string $sql
     * @param array|null $params
     * @param int $time
     * @param null $err
     */
    private function Log(string $sql, ?array $params = null, int $time = 0, $err = null): void
    {
        if (!static::$use_log)
            return;

        if (!$sql) {
            Debug::Halt('QuickDRY Error: empty query');
        }

        $this->query_time += $time;
        $this->query_count++;
        if (!isset(self::$log[$sql])) {
            self::$log[$sql] = [
                'params' => [],
                'err' => [],
                'time' => [],
                'total_time' => 0,
                'avg_time' => 0,
                'count' => 0,
            ];
        }
        self::$log[$sql]['params'][] = $params;
        if ($err) {
            self::$log[$sql]['err'][] = $err;
        }
        self::$log[$sql]['time'][] = $time;
        self::$log[$sql]['total_time'] += $time;
        self::$log[$sql]['count']++;
        self::$log[$sql]['avg_time'] = self::$log[$sql]['total_time'] / self::$log[$sql]['count'];


    }

    /**
     * @return string
     */
    private static function SQLErrorsToString(): string
    {
        $errs = sqlsrv_errors();

        $res = [];

        foreach ($errs as $err) {
            $res[] = $err['SQLSTATE'] . ', ' . $err['code'] . ': ' . $err['message'];
        }
        return implode("\r\n", $res);
    }

    /**
     * @param $sql
     * @param ?array $params
     * @param null $map_function
     * @return array|mixed
     */
    private function QueryWindows($sql, array $params = null, $map_function = null): mixed
    {
        Metrics::Start('MSSQL');

        $start = microtime(true);

        $returnval = [
            'error' => 'command not executed',
            'numrows' => 0,
            'data' => [],
            'sql' => $sql,
            'params' => $params
        ];

        $query = MSSQL::EscapeQuery($sql, $params);
        $returnval['query'] = $query;

        $this->_connect();

        // If still no link, then the query will not run...
        if (!$this->db) {
            // Notify that DB is crashed
            $returnval['error'] = ['QueryWindows No DB Connection', $this->_LastConnection, $this->db_conns];
            Metrics::Stop('MSSQL');
            return $returnval;
        }
        try {
            $list = [];
            $result = sqlsrv_query($this->db, $query);

            if (!$result) {
                $returnval = ['error_type' => 'No Result Set', 'error' => static::SQLErrorsToString(), 'query' => $query, 'params' => $params];
                if ($returnval['error'] && defined('MYSQL_EXIT_ON_ERROR') && MYSQL_EXIT_ON_ERROR) {
                    Debug::Halt($returnval);
                }
            } else {
                while ($r = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
                    $list[] = is_null($map_function) ? $r : call_user_func($map_function, $r);
                }
                $more = [];
                $i = 0;
                while (sqlsrv_next_result($result)) {
                    $more[$i] = [];
                    while ($r = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
                        if (is_null($map_function)) {
                            $more[$i][] = $r;
                        } else {
                            $list[] = call_user_func($map_function, $r);
                        }
                    }
                    $i++;
                }
                $returnval = [
                    'data' => $list,
                    'error' => '',
                    'time' => microtime(true) - $start,
                    'query' => $query,
                    'params' => $params,
                    'sql' => $sql
                ];
                if (sizeof($more)) {
                    $returnval['more'] = $more;
                }
                sqlsrv_free_stmt($result);
            }
        } catch (Exception $e) {

            $returnval['error_type'] = 'Exception';
            $returnval['error'] = $e->getMessage();
            $returnval['sql'] = print_r([$sql, $params], true);

            Metrics::Stop('MSSQL');
            if ($map_function || (defined('MSSQL_EXIT_ON_ERROR') && MSSQL_EXIT_ON_ERROR)) {
                Debug::Halt($returnval);
            }
            return $returnval;
        }

        $t = microtime(true) - $start;
        Metrics::Stop('MSSQL');
        $this->Log($sql, $params, $t, $returnval['error']);

        if ($returnval['error']) {
            if ($map_function) {
                if (defined('MSSQL_EXIT_ON_ERROR') && MSSQL_EXIT_ON_ERROR) {
                    Debug::Halt($returnval);
                }
                return $returnval;
            }
        }
        if (!$map_function) {
            return $returnval;
        }
        return $returnval['data'];
    }

    /**
     * @param $sql
     * @param ?array $params
     * @param null $map_function
     * @return array|mixed
     */
    public function Query($sql, array $params = null, $map_function = null): mixed
    {
        $this->_connect();

        if ($this->_usesqlsrv)
            return $this->QueryWindows($sql, $params, $map_function);

        Metrics::Start('MSSQL');

        $start = microtime(true);

        $returnval = [
            'error' => 'command not executed',
            'numrows' => 0,
            'data' => [],
            'sql' => $sql,
            'params' => $params
        ];

        $query = MSSQL::EscapeQuery($sql, $params);
        $returnval['query'] = $query;

        // If still no link, then the query will not run...
        if (!$this->db) {
            Metrics::Stop('MSSQL');
            // Notify that DB is crashed
            $returnval['error'] = 'Query No DB Connection';
            return $returnval;
        }
        try {
            $list = [];
            $result = false;
            $query = MSSQL::EscapeQuery($sql, $params);

            if (!function_exists('mssql_query')) {
                exit('mssql_query');
            }
            if (!function_exists('mssql_get_last_message')) {
                exit('mssql_get_last_message');
            }
            if (!function_exists('mssql_fetch_assoc')) {
                exit('mssql_fetch_assoc');
            }

            if (defined('QUERY_RETRY')) {
                $count = 0;
                while (!$result && $count <= QUERY_RETRY) {
                    $result = mssql_query($query, $this->db);
                    if (!$result) {
                        if (mssql_get_last_message()) {
                            break;
                        } else {
                            $this->db = null;
                            $this->db_conns[$this->current_db] = null;
                            sleep(30);
                            $this->_connect();
                        }
                    }
                    $count++;
                }
            } else {
                $result = mssql_query($query, $this->db);
            }
            if (!$result) {
                $returnval = ['error' => print_r(mssql_get_last_message(), true), 'query' => $query, 'params' => $params, 'sql' => $sql];
                if ($returnval['error']) {
                    Debug::Halt($returnval);
                }
            } else {
                while ($r = mssql_fetch_assoc($result)) {
                    $list[] = is_null($map_function) ? $r : call_user_func($map_function, $r);
                }
                $returnval = ['data' => $list, 'error' => '', 'time' => microtime(true) - $start, 'query' => $sql, 'params' => $params];
            }
        } catch (Exception $e) {

            $returnval['error'] = 'Exception: ' . $e->getMessage();
            $returnval['sql'] = print_r([$sql, $params], true);
            if ($map_function || (defined('MSSQL_EXIT_ON_ERROR') && MSSQL_EXIT_ON_ERROR)) {
                Debug::Halt($returnval);
            }
            Metrics::Stop('MSSQL');
            return $returnval;
        }

        $t = microtime(true) - $start;
        Metrics::Stop('MSSQL');

        $this->Log($sql, $params, $t, $returnval['error']);

        if ($returnval['error']) {
            if ($map_function || (defined('MSSQL_EXIT_ON_ERROR') && MSSQL_EXIT_ON_ERROR)) {
                Debug::Halt($returnval);
            }
        }

        if (!$map_function || $returnval['error']) {
            return $returnval;
        }
        return $returnval['data'];
    }

    /**
     * @param $query
     * @param bool $large
     * @return QueryExecuteResult|null
     */
    public function ExecuteWindows($query, bool $large = false): ?QueryExecuteResult
    {
        Metrics::Start('MSSQL');

        $start = microtime(true);

        $returnval = ['error' => 'command not executed',
            'numrows' => 0,
            'data' => [],
            'query' => $query
        ];

        $this->_connect();


        // If still no link, then the query will not run...
        if (!$this->db) {
            // Notify that DB is crashed
            $returnval['error'] = 'ExecuteWindows No DB Connection';

            return new QueryExecuteResult($returnval);
        }
        try {
            if ($large) {
                if (!is_dir('sql')) {
                    mkdir('sql');
                }
                $fname = 'sql\\' . time() . '.' . rand(0, 10000) . '.mssql.sql';

                $fp = fopen($fname, 'w');
                while (!$fp) {
                    sleep(1);
                    $fname = 'sql\\' . time() . '.' . rand(0, 1000000) . '.mssql.sql';
                    $fp = fopen($fname, 'w');
                }
                fwrite($fp, str_replace("\r\n", "\n", $query));
                fclose($fp);
                $output = [];
                // -x turns off variable interpretation - must be set
                // https://docs.microsoft.com/en-us/sql/tools/sqlcmd-utility
                // adding -l 0 to avoid login timeout errors

                $opts = [];
                if (defined('MSSQL_TIMEOUT')) {
                    $opts[] = '-t' . MSSQL_TIMEOUT;
                }
                $opts[] = '-j';
                $opts[] = '-l 0';
                $opts[] = '-a 32767';
                $opts[] = '-x';
//                $opts[] = '-E';
                $opts[] = '-U"' . $this->DB_USER . '"';
                $opts[] = '-P"' . $this->DB_PASS . '"';
                $opts[] = '-S"' . $this->DB_HOST . '"';
                $opts[] = '-i"' . $fname . '"';

                $cmd = 'sqlcmd ' . implode(' ', $opts);

                if (self::$keep_files) {
                    Log::Insert('ExecuteWindows: cmd ' . $cmd);
                }

                $res = exec($cmd, $output);
                if (self::$keep_files) {
                    Log::Insert(['ExecuteWindows: output' => $output]);
                }

                $returnval['error'] = [];
                foreach ($output as $i => $line) {
                    if (preg_match('/Msg \d+, Level \d+, State \d+/i', $line)) {
                        $error = implode(', ', [$line, $output[$i + 1], $fname]);
                        if ($this->IgnoreDuplicateError && stristr($error, 'The duplicate key value is') !== false) {
                            continue;
                        }
                        if ($this->IgnoreDuplicateError && stristr($error, 'Cannot insert duplicate key row') !== false) {
                            continue;
                        }
                        $returnval['error'][$i] = 'IgnoreDuplicateError - ' . $this->IgnoreDuplicateError . ': ' . $error;
                    }
                }
                if (sizeof($returnval['error'])) {
                    $returnval['error'] = trim(implode("\r\n", $returnval['error']));
                } else {
                    $returnval['error'] = '';
                }

                $returnval['exec'] = $res . PHP_EOL . PHP_EOL . implode(
                        PHP_EOL, $output
                    );
                if (!self::$keep_files) {
                    unlink($fname);
                } elseif (stristr($returnval['exec'], 'Timeout expired') !== false) {
                    $newname = 'sql/timeout.' . str_replace('sql\\', '', $fname) . '.txt';
                    rename($fname, $newname);
                    $returnval['error'] = 'timeout';
                    $returnval['query'] = $query;
                } else {
                    rename($fname, $fname . '.txt');
                }

            } else {
                $result = sqlsrv_query($this->db, $query);
                if (!$result) {
                    $returnval = ['error' => static::SQLErrorsToString(),
                        'query' => $query];
                } else {
                    $returnval['error'] = '';
                    $returnval['numrows'] = sqlsrv_rows_affected($result);
                }
                if ($result) {
                    sqlsrv_free_stmt($result);
                }
            }
        } catch (Exception $e) {
            Metrics::Stop('MSSQL');
            $returnval['error'] = 'Exception: ' . $e->getMessage();
            $returnval['query'] = $query;

            return new QueryExecuteResult($returnval);
        }

        $t = microtime(true) - $start;
        Metrics::Stop('MSSQL');
        if (!$large) {
            $this->Log($query, null, $t, $returnval['error']);
        }

        return new QueryExecuteResult($returnval);
    }

    /**
     * @param string $sql
     * @param ?array $params
     * @param bool $large
     * @return QueryExecuteResult
     */
    public function Execute(string $sql, array $params = null, bool $large = false): QueryExecuteResult
    {
        Metrics::Start('MSSQL');

        if (!is_null($params) && sizeof($params)) {
            $query = MSSQL::EscapeQuery($sql, $params);
        } else {
            $query = $sql;
        }

        $this->_connect();

        return $this->ExecuteWindows($query, $large);
    }

    /**
     * @return int|null
     */
    public function LastID(): ?int
    {
        $sql = '
					SELECT SCOPE_IDENTITY() AS lid
				';
        $res = $this->Query($sql);
        if(isset($res['data'][0]['lid'])) {
            return (int)$res['data'][0]['lid'];
        }
        return null;
    }

    /**
     * @return array
     */
    public function GetDatabases(): array
    {
        $sql = 'SELECT * FROM sys.databases ORDER BY name';
        $res = $this->Query($sql);
        $list = [];
        if ($res['error']) {
            Debug::Halt($res);
        }
        foreach ($res['data'] as $row) {
            $t = $row['name'];
            if (str_starts_with($t, 'TEMP'))
                continue;

            $list[] = $t;
        }
        return $list;
    }

    /**
     * @return array
     */
    public function GetTables(): array
    {
        $sql = 'SELECT * FROM [' . $this->current_db . '].information_schema.tables WHERE "TABLE_TYPE" <> \'VIEW\' ORDER BY "TABLE_NAME"';
        $res = $this->Query($sql);
        $list = [];
        if ($res['error']) {
            Debug::Halt($res);
        }

        foreach ($res['data'] as $row) {
            $t = $row['TABLE_NAME'];
            if (str_starts_with($t, 'TEMP'))
                continue;

            $list[] = $t;
        }
        return $list;
    }

    /**
     * @param $table_name
     *
     * @return array
     */
    public function GetTableColumns($table_name): array
    {
        $sql = '
			SELECT
				*
			FROM
				[' . $this->current_db . '].INFORMATION_SCHEMA.COLUMNS
			WHERE
				TABLE_NAME=@
		';
        $res = $this->Query($sql, [$table_name]);
        $list = [];
        foreach ($res['data'] as $row) {
            $t = new MSSQL_TableColumn();
            $t->FromRow($row);
            $list[] = $t;
        }
        return $list;
    }

    /**
     * @param $table_name
     *
     * @return array
     */
    public function GetTableIndexes($table_name): array
    {
        $sql = '
			SELECT
				OBJECT_SCHEMA_NAME(T.[object_id],DB_ID()) AS [Schema],
  				T.[name] AS [table_name],
				I.[name] AS [index_name],
				AC.[name] AS [column_name],
  				I.[type_desc],
				I.[is_unique],
				I.[data_space_id],
				I.[ignore_dup_key],
				I.[is_primary_key],
  				I.[is_unique_constraint],
				I.[fill_factor],
				I.[is_padded],
				I.[is_disabled],
				I.[is_hypothetical],
  				I.[allow_row_locks],
				I.[allow_page_locks],
				IC.[is_descending_key],
				IC.[is_included_column]
			FROM
				[' . $this->current_db . '].sys.[tables] AS T
  			INNER JOIN [' . $this->current_db . '].sys.[indexes] I ON T.[object_id] = I.[object_id]
  			INNER JOIN [' . $this->current_db . '].sys.[index_columns] IC ON I.[object_id] = IC.[object_id]
  			INNER JOIN [' . $this->current_db . '].sys.[all_columns] AC ON T.[object_id] = AC.[object_id] AND IC.[column_id] = AC.[column_id]
			WHERE
				T.[is_ms_shipped] = 0
				AND I.[type_desc] <> \'HEAP\'
				AND T.[name] = @
			ORDER BY
				T.[name],
				I.[index_id],
				IC.[key_ordinal]
		';
        $res = $this->Query($sql, [$table_name]);
        $indexes = [];
        foreach ($res['data'] as $row) {
            $indexes[$row['index_name']]['is_unique'] = $row['is_unique'];
            $indexes[$row['index_name']]['is_primary_key'] = $row['is_primary_key'];
            $indexes[$row['index_name']]['columns'][] = $row['column_name'];
        }
        return $indexes;
    }

    private static ?array $_UniqueKeys = null;
    private static ?array $_Indexes = null;

    /**
     * @param $table_name
     * @return mixed
     */
    public function GetIndexes($table_name): mixed
    {
        if (is_null(self::$_Indexes)) {
            $this->GetUniqueKeys($table_name);
        }
        if (!isset(self::$_Indexes[$table_name])) {
            self::$_Indexes[$table_name] = [];
        }
        return self::$_Indexes[$table_name];
    }

    /**
     * @param $table_name
     * @return mixed
     */
    public function GetUniqueKeys($table_name): mixed
    {
        if (is_null(self::$_UniqueKeys)) {
            self::$_UniqueKeys = [];
            self::$_Indexes = [];
            // https://stackoverflow.com/questions/765867/list-of-all-index-index-columns-in-sql-server-db
            $sql = '
SELECT
     TableName = t.name,
     IndexName = ind.name,
     IndexId = ind.index_id,
     ColumnId = ic.index_column_id,
     ColumnName = col.name,
     ind.*,
     ic.*,
     col.*
FROM
     [' . $this->current_db . '].sys.indexes ind
INNER JOIN
     [' . $this->current_db . '].sys.index_columns ic ON  ind.object_id = ic.object_id and ind.index_id = ic.index_id
INNER JOIN
     [' . $this->current_db . '].sys.columns col ON ic.object_id = col.object_id and ic.column_id = col.column_id
INNER JOIN
     [' . $this->current_db . '].sys.tables t ON ind.object_id = t.object_id

ORDER BY
     t.name, ind.name, ind.index_id, ic.index_column_id

        ';

            $res = $this->Query($sql);
            if ($res['error']) {
                Debug::Halt($res);
            }
            foreach ($res['data'] as $row) {
                if ($row['is_primary_key']) {
                    continue;
                }
                if ($row['is_unique']) {
                    if (!isset(self::$_UniqueKeys[$row['TableName']][$row['IndexName']])) {
                        self::$_UniqueKeys[$row['TableName']][$row['IndexName']] = [];
                    }
                    self::$_UniqueKeys[$row['TableName']][$row['IndexName']][] = $row['ColumnName'];
                } else {
                    if (!isset(self::$_Indexes[$row['TableName']][$row['IndexName']])) {
                        self::$_Indexes[$row['TableName']][$row['IndexName']] = [];
                    }
                    self::$_Indexes[$row['TableName']][$row['IndexName']][] = $row['ColumnName'];
                }
            }
        }
        if (!isset(self::$_UniqueKeys[$table_name])) {
            self::$_UniqueKeys[$table_name] = [];
        }
        return self::$_UniqueKeys[$table_name];
    }

    /**
     * @param $table_name
     *
     * @return array
     */

    private static ?array $_ForeignKeys = null;

    // https://stackoverflow.com/questions/483193/how-can-i-list-all-foreign-keys-referencing-a-given-table-in-sql-server
    // Note: SYS Tables are far more reliable for this.  Using the information schema table, you will not accurately get
    // foreign keys that link to UNIQUE indexes, only ones that link to PRIMARY Keys
    /**
     * @param $table_name
     * @return mixed
     */
    public function GetForeignKeys($table_name): mixed
    {
        if (!isset(self::$_ForeignKeys[$this->current_db])) {

            $sql = '
SELECT
    obj.name AS FK_CONSTRAINT_NAME
    , sch.name AS FK_DATABASE_NAME
    , tab1.name AS FK_TABLE_NAME
    , col1.name AS FK_COLUMN_NAME
    , tab2.name AS REFERENCED_TABLE_NAME
    , col2.name AS REFERENCED_COLUMN_NAME
    , fkc.referenced_column_id AS REFERENCED_COLUMN_ID
FROM [' . $this->current_db . '].sys.foreign_key_columns fkc
INNER JOIN [' . $this->current_db . '].sys.objects obj ON obj.object_id = fkc.constraint_object_id
INNER JOIN [' . $this->current_db . '].sys.tables tab1 ON tab1.object_id = fkc.parent_object_id
INNER JOIN [' . $this->current_db . '].sys.schemas sch ON tab1.schema_id = sch.schema_id
INNER JOIN [' . $this->current_db . '].sys.columns col1 ON col1.column_id = parent_column_id AND col1.object_id = tab1.object_id
INNER JOIN [' . $this->current_db . '].sys.tables tab2 ON tab2.object_id = fkc.referenced_object_id
INNER JOIN [' . $this->current_db . '].sys.columns col2 ON col2.column_id = referenced_column_id AND col2.object_id = tab2.object_id
ORDER BY obj.name, fkc.referenced_column_id
            ';

            $res = $this->Query($sql);
            self::$_ForeignKeys[$this->current_db] = [];
            foreach ($res['data'] as $row) {
                if (!isset(self::$_ForeignKeys[$this->current_db][$row['FK_TABLE_NAME']])) {
                    self::$_ForeignKeys[$this->current_db][$row['FK_TABLE_NAME']] = [];
                }

                if (!isset(self::$_ForeignKeys[$this->current_db][$row['FK_TABLE_NAME']][$row['FK_CONSTRAINT_NAME']])) {
                    $fk = new MSSQL_ForeignKey();
                    $fk->FromRow($row);
                } else {
                    $fk = self::$_ForeignKeys[$this->current_db][$row['FK_TABLE_NAME']][$row['FK_CONSTRAINT_NAME']];
                    $fk->AddRow($row);
                }

                self::$_ForeignKeys[$this->current_db][$row['FK_TABLE_NAME']][$row['FK_CONSTRAINT_NAME']] = $fk;
            }
        }
        if (!isset(self::$_ForeignKeys[$this->current_db][$table_name])) {
            self::$_ForeignKeys[$this->current_db][$table_name] = [];
        }

        return self::$_ForeignKeys[$this->current_db][$table_name];
    }

    private static ?array $_LinkedTables = null;

    /**
     * @param $table_name
     * @return mixed
     */
    public function GetLinkedTables($table_name): mixed
    {
        if (!isset(self::$_LinkedTables[$this->current_db])) {
            $sql = '
			SELECT
			     KCU2.CONSTRAINT_NAME AS FK_CONSTRAINT_NAME
			    ,KCU2.TABLE_NAME AS FK_TABLE_NAME
			    ,KCU2.COLUMN_NAME AS FK_COLUMN_NAME
			    ,KCU2.ORDINAL_POSITION AS FK_ORDINAL_POSITION

			    ,KCU1.CONSTRAINT_NAME AS REFERENCED_CONSTRAINT_NAME
			    ,KCU1.TABLE_NAME AS REFERENCED_TABLE_NAME
			    ,KCU1.COLUMN_NAME AS REFERENCED_COLUMN_NAME
			    ,KCU1.ORDINAL_POSITION AS REFERENCED_ORDINAL_POSITION
			FROM [' . $this->current_db . '].INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS AS RC

			LEFT JOIN [' . $this->current_db . '].INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS KCU1
			    ON KCU1.CONSTRAINT_CATALOG = RC.CONSTRAINT_CATALOG
			    AND KCU1.CONSTRAINT_SCHEMA = RC.CONSTRAINT_SCHEMA
			    AND KCU1.CONSTRAINT_NAME = RC.CONSTRAINT_NAME

			LEFT JOIN [' . $this->current_db . '].INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS KCU2
			    ON KCU2.CONSTRAINT_CATALOG = RC.UNIQUE_CONSTRAINT_CATALOG
			    AND KCU2.CONSTRAINT_SCHEMA = RC.UNIQUE_CONSTRAINT_SCHEMA
			    AND KCU2.CONSTRAINT_NAME = RC.UNIQUE_CONSTRAINT_NAME
			    AND KCU2.ORDINAL_POSITION = KCU1.ORDINAL_POSITION
		ORDER BY KCU2.TABLE_NAME

		';
            $res = $this->Query($sql);

            if (isset($res['data'])) {
                foreach ($res['data'] as $row) {
                    if (!isset($fks[$row['REFERENCED_CONSTRAINT_NAME']])) {
                        $fk = new MSSQL_ForeignKey();
                        $fk->FromRow($row);
                    } else {
                        $fk = self::$_LinkedTables[$this->current_db][$row['FK_TABLE_NAME']][$row['REFERENCED_CONSTRAINT_NAME']];
                        $fk->AddRow($row);
                    }

                    if (!isset(self::$_LinkedTables[$this->current_db][$row['FK_TABLE_NAME']])) {
                        self::$_LinkedTables[$this->current_db][$row['FK_TABLE_NAME']] = [];
                    }
                    self::$_LinkedTables[$this->current_db][$row['FK_TABLE_NAME']][$row['REFERENCED_CONSTRAINT_NAME']] = $fk;
                }
            }
        }
        if (!isset(self::$_LinkedTables[$this->current_db][$table_name])) {
            self::$_LinkedTables[$this->current_db][$table_name] = [];
        }

        return self::$_LinkedTables[$this->current_db][$table_name];
    }

    /**
     * @param $table_name
     *
     * @return array
     */
    public function GetPrimaryKey($table_name): array
    {
        $sql = '
			SELECT
				column_name
			FROM
				[' . $this->current_db . '].INFORMATION_SCHEMA.KEY_COLUMN_USAGE
			WHERE
				OBJECTPROPERTY(OBJECT_ID(constraint_name), \'IsPrimaryKey\') = 1
				AND table_name = @
		';
        $res = $this->Query($sql, [$table_name]);
        if ($res['error']) {
            Debug::Halt($res);
        }
        $list = [];
        foreach ($res['data'] as $col) {
            $list[] = $col['column_name'];
        }
        return $list;
    }

    /**
     * @return MSSQL_Trigger[]
     */
    public function GetTriggers(): array
    {
        $sql = '
select * from [' . $this->current_db . '].sys.triggers ORDER BY name
        ';

        /* @var MSSQL_Trigger[] $res */
        $res = $this->Query($sql, null, function ($row) {
            $t = new MSSQL_Trigger($row);
            $sql = 'SELECT
    definition
FROM
    [' . $this->current_db . '].sys.sql_modules
WHERE
    object_id = @object_id
    ';
            $def = $this->Query($sql, ['object_id' => $t->object_id]);

            $t->definition = $def['data'][0]['definition'] ?? '';

            return $t;
        });
        if (isset($res['error'])) {
            Debug::Halt($res);
        }
        return $res;
    }

    /**
     * @return MSSQL_Definition[]
     */
    public function GetDefinitions(): array
    {
        $sql = '
select
   OBJECT_NAME(sm.object_id) AS object_name
   ,o.type_desc
   ,sm.definition
  from [' . $this->current_db . '].sys.sql_modules AS sm
  INNER JOIN [' . $this->current_db . '].sys.objects o ON sm.object_id = o.object_id
ORDER BY type_desc, OBJECT_NAME(sm.object_id)
        ';
        /* @var MSSQL_Definition[] $res */
        $res = $this->Query($sql, null, function ($row) {
            return new MSSQL_Definition($row);
        });
        if (isset($res['error'])) {
            Debug::Halt($res);
        }
        return $res;
    }

    /**
     * @return MSSQL_StoredProc[]
     */
    public function GetStoredProcs(): array
    {
        $sql = '
select
  routines.*
  ,object_definition(object_id) AS SOURCE_CODE
  from [' . $this->current_db . '].information_schema.routines
  INNER JOIN [' . $this->current_db . '].sys.objects ON objects.name = ROUTINE_NAME
 where routine_type = \'PROCEDURE\'
 ORDER BY SPECIFIC_NAME
        ';
        /* @var MSSQL_StoredProc[] $res */
        $res = $this->Query($sql, null, function ($row) {
            return new MSSQL_StoredProc($row);
        });
        if (isset($res['error'])) {
            Debug::Halt($res);
        }
        return $res;
    }

    private array $_StoredProcParams = [];

    /**
     * @param string $stored_proc
     * @return MSSQL_StoredProcParam[]
     */
    public function GetStoredProcParams(string $stored_proc): array
    {
        if (sizeof($this->_StoredProcParams)) {
            return $this->_StoredProcParams[$stored_proc] ?? [];
        }

        $sql = '
select
  \'StoredProc\' = object_name(object_id),
   \'Parameter_name\' = name,
   \'Type\'   = type_name(user_type_id),
   \'Length\'   = max_length,
   \'Prec\'   = case when type_name(system_type_id) = \'uniqueidentifier\'
              then precision
              else OdbcPrec(system_type_id, max_length, precision) end,
   \'Scale\'   = OdbcScale(system_type_id, scale),
   \'Param_order\'  = parameter_id,
   \'Collation\'   = convert(sysname,
                   case when system_type_id in (35, 99, 167, 175, 231, 239)
                   then ServerProperty(\'collation\') end)

  from [' . $this->current_db . '].sys.parameters
  ORDER BY parameter_id
        ';

        $res = $this->Query($sql);
        foreach ($res['data'] as $row) {
            if (!isset($this->_StoredProcParams[$row['StoredProc']])) {
                $this->_StoredProcParams[$row['StoredProc']] = [];
            }
            $this->_StoredProcParams[$row['StoredProc']][] = new MSSQL_StoredProcParam($row);
        }

        Log::Insert('Got Stored Procs');
        return $this->_StoredProcParams[$stored_proc] ?? [];
    }
}