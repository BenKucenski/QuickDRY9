<?php

namespace Bkucenski\Quickdry\Connectors\mysql;

/** DO NOT USE THIS CLASS DIRECTLY **/

use Exception;
use mysqli;
use Bkucenski\Quickdry\Connectors\mssql\MSSQL_ForeignKey;
use Bkucenski\Quickdry\Connectors\mysql;
use Bkucenski\Quickdry\Connectors\QueryExecuteResult;
use Bkucenski\Quickdry\Utilities\Debug;
use Bkucenski\Quickdry\Utilities\Log;
use Bkucenski\Quickdry\Utilities\Metrics;

/**
 * Class MySQL
 */
class MySQL_Connection
{
    public ?bool $IgnoreDuplicateError = true;

    public static bool $use_log = false;
    public static bool $log_queries_to_file = false;
    public static bool $keep_files = false;
    public static array $log = [];

    protected array $db_conns = [];
    protected ?mysqli $db = null;
    protected ?string $current_db = null;
    protected string $DB_HOST;
    protected string $DB_USER;
    protected string $DB_PASS;
    protected int $DB_PORT;

    /**
     * @param $host
     * @param $user
     * @param $pass
     * @param $port
     */
    public function __construct($host, $user, $pass, $port = null)
    {
        $this->DB_HOST = $host;
        $this->DB_USER = $user;
        $this->DB_PASS = $pass;
        $this->DB_PORT = $port ?: 3306;
        $this->_connect();
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
     * @param $db_base
     *
     * @return bool
     */
    public function CheckDatabase($db_base): bool
    {
        $sql = '
SELECT 
       SCHEMA_NAME 
FROM INFORMATION_SCHEMA.SCHEMATA
WHERE SCHEMA_NAME = :SCHEMA_NAME
';
        $res = $this->Query($sql, ['SCHEMA_NAME' => $db_base]);

        return isset($res['data'][0]['SCHEMA_NAME']);
    }

    /**
     * @param $sql
     * @param $params
     * @return array|string|string[]|null
     */
    public function EscapeQuery($sql, $params): array|string|null
    {
        return MySQL::EscapeQuery($this->db, $sql, $params);
    }

    /**
     * @param $db_base
     */
    public function SetDatabase($db_base): void
    {
        if (!$db_base) {
            $db_base = $this->current_db;
        }

        if ($this->db && !mysqli_ping($this->db)) {
            $this->db_conns[$this->current_db] = null;
            $this->current_db = null;
        }

        if ($db_base && strcmp($this->current_db, $db_base) == 0) {
            return;
        }

        if (!isset($this->db_conns[$db_base]) || is_null($this->db_conns[$db_base])) {
            try {
                if (defined('MYSQL_SSL') && MYSQL_SSL) {
                    $con = mysqli_init();
                    mysqli_options($con, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
                    mysqli_ssl_set($con
                        , NULL
                        , NULL
                        , NULL
                        , NULL
                        , NULL
                    );
                    mysqli_real_connect(
                        $con,
                        $this->DB_HOST,
                        $this->DB_USER,
                        $this->DB_PASS,
                        $db_base,
                        $this->DB_PORT
                    );
                    $this->db_conns[$db_base] = $con;
                } else {
                    $this->db_conns[$db_base] = mysqli_connect(
                        $this->DB_HOST,
                        $this->DB_USER,
                        $this->DB_PASS,
                        $db_base,
                        $this->DB_PORT
                    );
                }
                if (!$this->db_conns[$db_base]) {
                    Debug(['Could not connect', $this->DB_HOST, $this->DB_USER]);
                }

            } catch (Exception $e) {
                die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error() . PHP_EOL . print_r([$e->getMessage(), $this->DB_HOST, $this->DB_USER, md5($this->DB_PASS), $db_base], true));
            }
        }

        $this->db = $this->db_conns[$db_base];
        $this->current_db = $db_base;
    }

    /**
     * @param string $sql
     * @param int $time
     * @param string|null $err
     */
    private function Log(string $sql, int $time = 0, string $err = null): void
    {
        if (is_null($err)) {
            $err = mysqli_error($this->db);
        }

        if ($err && defined('MYSQL_EXIT_ON_ERROR') && MYSQL_EXIT_ON_ERROR) {
            Debug($err);
        }

        //$this->log[] = $sql;
        if (!static::$use_log) {
            return;
        }

        static::$log[] = ['query' => $sql, 'time' => $time];

    }

    /**
     * @param string $sql
     * @param array|null $params
     * @param bool $large
     * @return QueryExecuteResult
     */
    public function Execute(string $sql, array $params = null, bool $large = false): QueryExecuteResult
    {
        $query_hash = 'all';
        if (self::$log_queries_to_file) { // don't log as a single array because it makes the queries unreadable
            $query_hash = md5($sql);
            Log::Insert($query_hash);
            Log::Insert($sql);
            Log::Insert($params);
        }

        Metrics::Start('MySQL: ' . $query_hash);

        try {
            $this->_connect();

            if (!$this->db) {
                Debug([$sql, $params, 'mysql went away']);
            }

            $start = microtime(true);

            if ($params) {
                $sql = MySQL::EscapeQuery($this->db, $sql, $params);
            }

            $exec = '';
            $last_id = 0;
            $aff = 0;

            if ($large || strlen($sql) > 128 * 1024) {
                if (!is_dir('sql')) {
                    mkdir('sql');
                }
                $fname = 'sql/' . time() . rand(0, 1000000) . '.mysql.txt';
                $fp = fopen($fname, 'w');
                $tries = 0;
                while (!$fp && $tries < 3) {
                    sleep(1);
                    $fname = 'sql/' . time() . rand(0, 1000000) . '.mysql.txt';
                    $fp = fopen($fname, 'w');
                    $tries++;
                }
                if ($fp) {
                    fwrite($fp, $sql);
                    fclose($fp);
                } else {
                    Debug('QuickDRY Error: error writing mysql file');
                }

                $file = 'mysql_config.' . GUID . '.cnf';
                $fp = fopen($file, 'w');
                fwrite(
                    $fp, '
[client]
user = ' . $this->DB_USER . '
password = ' . $this->DB_PASS . '
host = ' . $this->DB_HOST . '
database = ' . $this->current_db . '
            '
                );
                fclose($fp);

                $output = [];
                // -x turns off variable interpretation - must be set
                $cmd = 'mysql --defaults-extra-file="' . $file . '" -P' . $this->DB_PORT . ' < ' . $fname;
                $res = exec($cmd, $output);

                $exec = $cmd . PHP_EOL . PHP_EOL . $res . PHP_EOL . PHP_EOL . implode(PHP_EOL, $output);
                $error = '';

                if (!static::$keep_files) {
                    unlink($fname);
                }
                if (file_exists($file)) {
                    unlink($file);
                }

                Metrics::Stop('MySQL: ' . $query_hash);
            } else {

                mysqli_begin_transaction($this->db);
                $res = mysqli_multi_query($this->db, $sql);
                if ($res) {
                    do {
                        /* store first result set */
                        if ($result = mysqli_store_result($this->db)) {
                            mysqli_free_result($result);
                        }
                        $aff += mysqli_affected_rows($this->db);
                    } while (mysqli_more_results($this->db)
                    && mysqli_next_result($this->db));
                }
                $last_id = $this->LastID();

                Metrics::Stop('MySQL: ' . $query_hash);

                $this->Log($sql, microtime(true) - $start);

                $error = mysqli_error($this->db);
                if ($error) {
                    mysqli_rollback($this->db);
                } else {
                    mysqli_commit($this->db);
                }
            }
            return new QueryExecuteResult([
                'error' => $error,
                'sql' => $sql,
                'last_id' => $last_id,
                'affected_rows' => $aff,
                'exec' => $exec,
            ]);
        } catch (Exception $e) {
            Debug($e);
        }
        return new QueryExecuteResult();
    }

    /**
     * @param string $sql
     * @param array|null $params
     * @param string|null $return_type
     * @param null $map_function
     * @return array|string
     */
    public function Query(
        string $sql,
        array $params = null,
        string $return_type = null,
        $map_function = null
    ): array|string
    {
        $query_hash = 'all';
        if (self::$log_queries_to_file) { // don't log as a single array because it makes the queries unreadable
            $query_hash = md5($sql);

            Log::Insert($query_hash);
            Log::Insert($sql);
            Log::Insert($params);
        }

        Metrics::Start('MySQL: ' . $query_hash);

        $this->_connect();

        $return = [
            'data' => ''
        ];

        if ($params) {
            $sql = MySQL::EscapeQuery($this->db, $sql, $params);
        }

        if (!$this->current_db) {
            $pattern = '/FROM\s+(.*?)\./si';
            $matches = [];
            preg_match_all($pattern, $sql, $matches);
            if (isset($matches[1][0])) {
                self::SetDatabase($matches[1][0]);
            } else {
                Debug(['QuickDRY Error' => 'Database not set and database could not be determined from query', 'sql' => $sql]);
            }
        }

        $start = microtime(true);
        $list = [];
        $res = false;
        if (defined('QUERY_RETRY')) {
            $count = 0;
            while (!$res && $count <= QUERY_RETRY) {
                $res = mysqli_query($this->db, $sql, MYSQLI_USE_RESULT);
                if (!$res) {
                    if (mysqli_error($this->db)) {
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
            $res = mysqli_query($this->db, $sql, MYSQLI_USE_RESULT);
        }

        if ($res && is_object($res)) {
            while ($r = mysqli_fetch_assoc($res)) {
                if (is_null($return_type)) {

                    $list[] = !is_null($map_function) ? call_user_func($map_function, $r) : $r;
                } else {
                    if (!class_exists($return_type)) {
                        Debug::Halt($return_type . ' does not exist: MySQL_Connection::Query');
                    }

                    $c = new $return_type();
                    $c->FromRow($r);
                    $list[] = $c;
                }
            }

            mysqli_free_result($res);

            do {
                /* store first result set */
                if ($result = mysqli_store_result($this->db)) {
                    mysqli_free_result($result);
                }
            } while (mysqli_more_results($this->db)
            && mysqli_next_result(
                $this->db
            ));
        }

        $return['error'] = mysqli_error($this->db);
        $return['sql'] = $sql;
        if (is_null($return_type)) {
            $return['data'] = $list;
        } else {
            $return[$return_type] = $list;
        }

        Metrics::Stop('MySQL: ' . $query_hash);

        $this->Log($sql, microtime(true) - $start, $return['error']);

        if ($return_type && !$return['error']) {
            return $return[$return_type];
        }

        if (!$map_function || $return['error']) {
            return $return;
        }

        return $return['data'];
    }

    /**
     * @param      $values
     * @param bool $quotes
     *
     * @return float|array|int|string
     */
    public function Escape($values, bool $quotes = true): float|array|int|string
    {
        $this->_connect();
        if (is_array($values)) {
            foreach ($values as $key => $value) {
                $values[$key] = $this->Escape($value, $quotes);
            }
        } elseif ($values === null) {
            $values = 'NULL';
        } elseif (is_bool($values)) {
            $values = $values ? 1 : 0;
        } elseif (strlen($values) != strlen($values * 1.0) || !is_numeric($values) || $values[0] == '0') {
            $values = mysqli_real_escape_string($this->db, $values);
            if ($quotes) {
                $values = '"' . $values . '"';
            }
        } elseif ($quotes) {
            $values = '"' . $values . '"';
        }

        return $values;
    }

    /**
     * @return int|string
     */
    public function LastID(): int|string
    {
        return mysqli_insert_id($this->db);
    }

    /**
     * @return array
     */
    public function GetTables(): array
    {
        $sql = '
			SHOW TABLES;
		';
        $res = $this->Query($sql);
        $tables = [];

        foreach ($res['data'] as $d) {
            $tables[$d['Tables_in_' . $this->current_db]] = $d['Tables_in_' . $this->current_db];
        }

        return $tables;
    }

    /**
     * @param string $table
     * @return array
     */
    public function GetTableColumns(string $table): array
    {
        $sql = '
			SHOW COLUMNS FROM
			    `{{nq}}`;
		';
        $res = $this->Query($sql, [$table]);
        if ($res['error']) {
            Debug($res);
        }

        $list = [];
        foreach ($res['data'] as $row) {
            $l = new MySQL_TableColumn();
            $l->FromRow($row);
            $list[] = $l;
        }
        return $list;
    }

    private static ?array $_LinkedTables = null;

    /**
     * @param string $table_name
     * @return mixed
     */
    public function GetLinkedTables(string $table_name): mixed
    {
        if (!isset(self::$_LinkedTables[$this->current_db])) {
            $sql = '
		SELECT
				table_name AS referenced_table_name,
				column_name AS referenced_column_name,
				referenced_table_name AS table_name,
				referenced_column_name AS column_name,
				CONSTRAINT_NAME
		FROM
				information_schema.key_column_usage
		WHERE
				referenced_table_schema = \'' . $this->current_db . '\'
		  		AND referenced_table_name IS NOT NULL
		ORDER BY referenced_table_name, table_name, column_name

		';
            $res = $this->Query($sql);
            if ($res['error']) {
                Debug($res);
            }

            /* @var MSSQL_ForeignKey $fk */
            if (sizeof($res['data'])) {
                foreach ($res['data'] as $row) {
                    if (!isset($fks[$row['CONSTRAINT_NAME']])) {
                        $fk = new MySQL_ForeignKey();
                        $fk->FromRow($row);
                    } else {
                        $fk = self::$_LinkedTables[$this->current_db][$row['table_name']][$row['CONSTRAINT_NAME']];
                        $fk->AddRow($row);
                    }

                    if (!isset(self::$_LinkedTables[$this->current_db][$row['table_name']])) {
                        self::$_LinkedTables[$this->current_db][$row['table_name']] = [];
                    }
                    self::$_LinkedTables[$this->current_db][$row['table_name']][$row['CONSTRAINT_NAME']] = $fk;
                }
            }
        }
        if (!isset(self::$_LinkedTables[$this->current_db][$table_name])) {
            self::$_LinkedTables[$this->current_db][$table_name] = [];
        }

        return self::$_LinkedTables[$this->current_db][$table_name];
    }


    private static ?array $_ForeignKeys = null;

    /**
     * @param $table_name
     * @return mixed
     */
    public function GetForeignKeys($table_name): mixed
    {
        if (!isset(self::$_ForeignKeys[$this->current_db])) {


            $sql = '
		SELECT
				table_name AS table_name,
				column_name AS column_name,
				referenced_table_name AS referenced_table_name,
				referenced_column_name AS referenced_column_name,
				CONSTRAINT_NAME
		FROM
				information_schema.key_column_usage
		WHERE
				referenced_table_schema = \'' . $this->current_db . '\'
		  		AND referenced_table_name IS NOT NULL
		ORDER BY referenced_table_name, table_name, column_name

		';
            $res = $this->Query($sql);

            if ($res['error']) {
                Debug::Halt($res);
            }

            self::$_ForeignKeys[$this->current_db] = [];
            foreach ($res['data'] as $row) {
                if (!isset(self::$_ForeignKeys[$this->current_db][$row['table_name']])) {
                    self::$_ForeignKeys[$this->current_db][$row['table_name']] = [];
                }

                /* @var MySQL_ForeignKey $fk */
                if (!isset(self::$_ForeignKeys[$this->current_db][$row['table_name']][$row['CONSTRAINT_NAME']])) {
                    $fk = new MySQL_ForeignKey();
                    $fk->FromRow($row);
                } else {
                    $fk = self::$_ForeignKeys[$this->current_db][$row['table_name']][$row['CONSTRAINT_NAME']];
                    $fk->AddRow($row);
                }

                self::$_ForeignKeys[$this->current_db][$row['table_name']][$row['CONSTRAINT_NAME']] = $fk;
            }
        }
        if (!isset(self::$_ForeignKeys[$this->current_db][$table_name])) {
            self::$_ForeignKeys[$this->current_db][$table_name] = [];
        }

        return self::$_ForeignKeys[$this->current_db][$table_name];
    }

    private static ?array $_PrimaryKey = null;

    /**
     * @param $table_name
     *
     * @return array|null
     */

    public function GetPrimaryKey($table_name): ?array
    {
        if (is_null(self::$_PrimaryKey) || !isset(self::$_PrimaryKey[$table_name])) {

            $sql = '
SHOW INDEXES FROM
`' . $this->current_db . '`.`' . $table_name . '`

        ';

            $res = $this->Query($sql);
            if ($res['error']) {
                Debug($res);
            }
            foreach ($res['data'] as $row) {
                if (!$row['Non_unique'] && $row['Key_name'] === 'PRIMARY') {
                    if (!isset(self::$_PrimaryKey[$row['Table']][$row['Key_name']])) {
                        self::$_PrimaryKey[$row['Table']] = [];
                    }
                    self::$_PrimaryKey[$row['Table']][] = $row['Column_name'];
                }
            }
        }
        if (!isset(self::$_PrimaryKey[$table_name])) {
            self::$_PrimaryKey[$table_name] = [];
        }
        return self::$_PrimaryKey[$table_name];
    }

    private static ?array $_UniqueKeys = null;
    private static ?array $_Indexes = null;

    /**
     * @param $table_name
     *
     * @return array
     */
    public function GetIndexes($table_name): array
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
     *
     * @return array|null
     */

    public function GetUniqueKeys($table_name): ?array
    {
        if (is_null(self::$_UniqueKeys) || !isset(self::$_UniqueKeys[$table_name])) {

            $sql = '
SHOW INDEXES FROM
`' . $this->current_db . '`.`' . $table_name . '`

        ';

            $res = $this->Query($sql);
            if ($res['error']) {
                Debug($res);
            }
            foreach ($res['data'] as $row) {
                if ($row['Key_name'] === 'PRIMARY') {
                    continue;
                }

                if (!$row['Non_unique']) {
                    if (!isset(self::$_UniqueKeys[$row['Table']][$row['Key_name']])) {
                        self::$_UniqueKeys[$row['Table']][$row['Key_name']] = [];
                    }
                    self::$_UniqueKeys[$row['Table']][$row['Key_name']][] = $row['Column_name'];
                } else {
                    if (!isset(self::$_Indexes[$row['Table']][$row['Key_name']])) {
                        self::$_Indexes[$row['Table']][$row['Key_name']] = [];
                    }
                    self::$_Indexes[$row['Table']][$row['Key_name']][] = $row['Column_name'];
                }
            }
        }
        if (!isset(self::$_UniqueKeys[$table_name])) {
            self::$_UniqueKeys[$table_name] = [];
        }
        return self::$_UniqueKeys[$table_name];
    }

    /**
     * @return array|string
     */
    public function GetStoredProcs(): array|string
    {
        $sql = '
SELECT
  t100.ROUTINE_TYPE,
  t100.ROUTINE_SCHEMA,
  t100.SPECIFIC_NAME,
  t100.ROUTINE_DEFINITION,
  t110.PARAMETERS

FROM information_schema.ROUTINES t100
LEFT JOIN (
SELECT SPECIFIC_SCHEMA, SPECIFIC_NAME, GROUP_CONCAT(DATA_TYPE,\':\', PARAMETER_NAME ORDER BY ORDINAL_POSITION) AS PARAMETERS
FROM information_schema.parameters
WHERE PARAMETER_MODE = \'IN\'
GROUP BY SPECIFIC_SCHEMA, SPECIFIC_NAME
) AS t110 ON t110.SPECIFIC_SCHEMA = t100.ROUTINE_SCHEMA AND t110.SPECIFIC_NAME = t100.SPECIFIC_NAME
WHERE (t100.ROUTINE_TYPE IN(\'FUNCTION\',\'PROCEDURE\'))
  AND t100.ROUTINE_SCHEMA = \'' . $this->current_db . '\'
ORDER BY
  t100.SPECIFIC_NAME;
		';
        $res = $this->Query($sql, null, MySQL_StoredProc::class);
        if (isset($res['error'])) {
            Debug($res);
        }
        return $res;
    }

    /**
     * @param string $specific_name
     * @return array|string
     */
    public function GetStoredProcParams(string $specific_name): array|string
    {
        $sql = '
SELECT
  t110.*

FROM information_schema.ROUTINES t100
LEFT JOIN information_schema.parameters t110  ON t110.SPECIFIC_SCHEMA = t100.ROUTINE_SCHEMA AND t110.SPECIFIC_NAME = t100.SPECIFIC_NAME
WHERE t110.PARAMETER_MODE = \'IN\'
AND (t100.ROUTINE_TYPE IN(\'FUNCTION\',\'PROCEDURE\'))
  AND t100.ROUTINE_SCHEMA = \'' . $this->current_db . '\'
  AND t100.SPECIFIC_NAME = \'' . $specific_name . '\'
		';
        $res = $this->Query($sql, null, MySQL_StoredProcParam::class);
        if (isset($res['error'])) {
            Debug($res);
        }
        return $res;
    }

    /**
     * @return void
     */
    public function CopyInfoSchema(): void
    {
        // unreliable, don't do this
//    $this->SetDatabase('INFORMATION_SCHEMA');
//    $this->Execute("DROP DATABASE IF EXISTS `info_schema`;", null, true);
//    $this->Execute("CREATE DATABASE  `info_schema` ;       ", null, true);
//    $this->Execute("CREATE TABLE info_schema.key_column_usage LIKE INFORMATION_SCHEMA.key_column_usage;", null, true);
//    $this->Execute("ALTER TABLE info_schema.key_column_usage ENGINE = INNODB;", null, true);
//    $this->Execute("ALTER TABLE info_schema.key_column_usage ADD INDEX (`referenced_table_schema`);", null, true);
//    $this->Execute("ALTER TABLE info_schema.key_column_usage ADD INDEX (`referenced_table_name`);", null, true);
//    $this->Execute("ALTER TABLE info_schema.key_column_usage ADD INDEX (`referenced_column_name`);", null, true);
//    $this->Execute("ALTER TABLE info_schema.key_column_usage ADD INDEX (`table_schema`);", null, true);
//    $this->Execute("ALTER TABLE info_schema.key_column_usage ADD INDEX (`table_name`);", null, true);
//    $this->Execute("ALTER TABLE info_schema.key_column_usage ADD INDEX (`column_name`);", null, true);
//    $this->Execute("INSERT INTO info_schema.key_column_usage SELECT * FROM INFORMATION_SCHEMA.key_column_usage;", null, true);

    }
}