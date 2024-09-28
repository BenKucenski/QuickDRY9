<?php

namespace Bkucenski\Quickdry\Connectors\mysql;

use Bkucenski\Quickdry\Connectors\mssql\MSSQL_Core;
use Bkucenski\Quickdry\Connectors\SQL_Base;
use Bkucenski\Quickdry\Connectors\SQLCodeGen;
use Bkucenski\Quickdry\Utilities\Log;

/**
 * Class MySQL_CodeGen
 */
class MySQL_CodeGen extends SQLCodeGen
{
    /**
     * @param $database
     * @param $database_constant
     * @param $user_class
     * @param $user_var
     * @param $user_id_column
     * @param $master_page
     * @param $lowercase_tables
     * @param $use_fk_column_name
     * @param string|null $DatabaseClass
     * @param bool $GenerateJSON
     * @param string $DestinationFolder
     * @return void
     */
    public function Init(
        $database,
        $database_constant,
        $user_class,
        $user_var,
        $user_id_column,
        $master_page,
        $lowercase_tables,
        $use_fk_column_name,
        string $DatabaseClass = null,
        bool $GenerateJSON = true,
        string $DestinationFolder = '../httpdocs'
    ): void
    {
        $this->DatabaseTypePrefix = 'my';

        if (!$DatabaseClass) {
            $DatabaseClass = 'QuickDRY\Connectors\mysql\MySQL_A';
        }
        if (!class_exists($DatabaseClass)) {
            exit($DatabaseClass . ' is invalid');
        }
        $this->DestinationFolder = $DestinationFolder;
        $this->DatabaseClass = $DatabaseClass;
        $this->Database = $database;
        $this->DatabaseConstant = $database_constant;
        $this->UserClass = $user_class ?: 'UserClass';
        $this->UserVar = $user_var ?: 'CurrentUser';
        $this->UserIdColumn = $user_id_column ?: 'id';
        $this->MasterPage = $master_page ?: 'MASTERPAGE_DEFAULT';
        $this->DatabasePrefix = $this->DatabaseConstant ?: $this->Database;
        $this->LowerCaseTables = $lowercase_tables;
        $this->UseFKColumnName = $use_fk_column_name;
        $this->GenerateJSON = $GenerateJSON;

        /* @var MySQL_Core $DatabaseClass */
        $DatabaseClass::CopyInfoSchema();

        $DatabaseClass::SetDatabase($this->Database);

        Log::Insert('$this->Tables = ' . $DatabaseClass . '::GetTables();');
        $this->Tables = $DatabaseClass::GetTables();

        $this->CreateDirectories();
    }

    /**
     * @return array|null
     */
    public function GenerateDatabaseClass(): ?array
    {
        $DatabaseClass = $this->DatabaseClass;
        $class_name = $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix);

        if (!method_exists($DatabaseClass, 'GetStoredProcs')) {
            exit("$DatabaseClass::GetStoredProcs");
        }

        $stored_procs = $DatabaseClass::GetStoredProcs();

        if (!$stored_procs) {
            return null;
        }
        $sp_require = [];
        foreach ($stored_procs as $sp) {
            /* @var MySQL_StoredProc $sp */
            $sp_class = SQL_Base::TableToClass(
                $this->DatabasePrefix,
                $sp->SPECIFIC_NAME,
                true,
                $this->DatabaseTypePrefix . '_sp'
            );

            Log::Insert($sp_class);

            $this->GenerateSPClassFile($sp_class);

            $sp_require['db_' . $sp_class] = 'common/' . $class_name . '/sp_db/db_' . $sp_class . '.php';
            $sp_require[$sp_class] = 'common/' . $class_name . '/sp/' . $sp_class . '.php';

            if (!method_exists($DatabaseClass, 'GetStoredProcParams')) {
                exit("$DatabaseClass::GetStoredProcParams");
            }
            /* @var MySQL_StoredProcParam[] $sp_params */
            $sp_params = $DatabaseClass::GetStoredProcParams($sp->SPECIFIC_NAME);
            $params = [];
            $sql_params = [];
            $func_params = [];
            foreach ($sp_params as $param) {
                $type = SQLCodeGen::ColumnTypeToProperty($param->DATA_TYPE, $this->DatabaseType);
                $clean_param = str_replace('#', '_', str_replace('@', '$', $param->PARAMETER_NAME));
                $sql_param = '{{' . str_replace('$', '', $clean_param) . '}}';
                $func_params[] = $type . ' $' . $clean_param;
                $sql_params[] = $sql_param;
                $params[] = '\'' . str_replace('$', '', $clean_param) . '\' => ' . '$' . $clean_param;
            }

            $template = file_get_contents(__DIR__ . '/../_templates/sp_db_mysql.txt');
            $vars = [
                'sp_class' => $sp_class,
                'func_params' => implode(', ', $func_params),
                'DatabaseConstant' => $this->DatabaseConstant ? '\'' . $this->DatabaseConstant . '\'.' : '[' . $this->Database . ']',
                'sql_params' => implode("\n         ,", $sql_params),
                'params' => implode(', ', $params),
                'SPECIFIC_NAME' => $sp->SPECIFIC_NAME,
                'DatabaseClass' => $DatabaseClass,

            ];

            $include_php = $template;
            foreach ($vars as $name => $v) {
                $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
            }

            $file = $this->CommonClassSPDBFolder . '/db_' . $sp_class . '.php';
            $fp = fopen($file, 'w');
            fwrite($fp, $include_php);
            fclose($fp);
        }

        return $sp_require;
    }
}