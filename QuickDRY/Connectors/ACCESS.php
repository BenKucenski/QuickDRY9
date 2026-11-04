<?php
declare(strict_types=1);

namespace QuickDRY\Connectors;

use Exception;

/**
 * Class ACCESS
 *
 * @property resource connection
 */
class ACCESS
{
    protected $connection = null;
    protected ?string $ACCESS_FILE = null;
    protected ?string $ACCESS_USER = null;
    protected ?string $ACCESS_PASS = null;

    /**
     *
     */
    public function __destruct()
    {
        if(!$this->connection) {
            return;
        }
        odbc_close($this->connection);
    }

    /**
     * @param string $sql
     * @param array|null $params
     * @return string
     */
    public function EscapeQuery(string $sql, ?array $params = null): string
    {
        if ($params) {
            foreach ($params as $k => $v) {
                $v = str_replace('\'', '\'\'', $v);
                if(is_numeric($v) && strcasecmp($v, intval($v) . '') === 0) {
                    $sql = str_replace('@' . $k, $v, $sql);
                } else {
                    $sql = str_replace('@' . $k, '\'' . trim($v) . '\'', $sql);
                }
            }
        }
        return $sql;
    }

    /**
     * @param string $file
     * @param string|null $user
     * @param string|null $pass
     * @param bool $skip_check
     */
    public function __construct(
        string  $file,
        ?string $user = null,
        ?string $pass = null,
        bool    $skip_check = false
    )
    {
        if(!file_exists($file)) {
            Exception('could not find database: ' . $file);
        }


        while(!$skip_check && file_exists(str_ireplace('.tmo', '.ldb', $file))) {
            // https://stackoverflow.com/questions/15322371/php-wait-for-input-from-command-line
            echo 'Database in Use By Another Program.  Please Exit All Instances of TMO' . PHP_EOL;
            $handle = fopen ('php://stdin', 'r');
            fgets($handle);
            fclose($handle);
        }

        $this->ACCESS_FILE = $file;
        $this->ACCESS_USER = $user;
        $this->ACCESS_PASS = $pass;

        try {
            $this->connection = odbc_connect('Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq=' . $file, $user, $pass);
        } catch (Exception $ex) {
            Exception($ex->getMessage());
        }
    }

    /**
     * @param string $sql
     * @param array|null $params
     * @return array
     */
    public function Query(string $sql, ?array $params = null): array
    {
        if (!$this->connection) {
            Exception('Not Connected');
        }

        $query = $this->EscapeQuery($sql, $params);

        $res = odbc_exec($this->connection, $query);
        if (odbc_error($this->connection)) {
            Exception([
                'sql' => $sql,
                'params' => $params,
                'query' => $query,
                'error' => 'ACCESS',
                'odbc_errormsg' => odbc_errormsg($this->connection),
            ]);
        }

        $list = [];
        while ($row = odbc_fetch_array($res)) {
            $list[] = $row;
        }
        return $list;
    }

    /**
     * @param string $sql
     * @param array|null $params
     * @return void
     */
    public function Execute(string $sql, ?array $params = null): void
    {
        if (!$this->connection) {
            Exception('Not Connected');
        }

        $query = $this->EscapeQuery($sql, $params);

        odbc_exec($this->connection, $query);
        if (odbc_error($this->connection)) {
            Exception([
                'sql' => $sql,
                'params' => $params,
                'query' => $query,
                'error' => 'ACCESS',
                'odbc_errormsg' => odbc_errormsg($this->connection),
            ]);
        }
    }

    /**
     * @param string $sql
     * @param array|null $params
     * @param callable $func
     * @param bool $return
     * @return array
     */
    public function QueryMap(string $sql, ?array $params, callable $func, bool $return = true): array
    {
        if (!$this->connection) {
            Exception('Not Connected');
        }

        $query = $this->EscapeQuery($sql, $params);

        $res = odbc_exec($this->connection, $query);
        if (odbc_error($this->connection)) {
            Exception([
                'sql' => $sql,
                'params' => $params,
                'query' => $query,
                'error' => 'ACCESS',
                'odbc_errormsg' => odbc_errormsg($this->connection),
            ]);
        }

        $list = [];
        while ($row = odbc_fetch_array($res)) {
            if ($return) {
                $list[] = call_user_func($func, $row);
            } else {
                call_user_func($func, $row);
            }
        }
        return $list;
    }
}