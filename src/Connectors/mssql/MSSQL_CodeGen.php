<?php

namespace Bkucenski\Quickdry\Connectors\mssql;

use Bkucenski\Quickdry\Connectors\SQL_Base;
use Bkucenski\Quickdry\Connectors\SQLCodeGen;
use Bkucenski\Quickdry\Utilities\Log;

/**
 * Class MSSQL_CodeGen
 */
class MSSQL_CodeGen extends SQLCodeGen
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
        $this->DatabaseTypePrefix = 'ms';

        if (!$DatabaseClass) {
            $DatabaseClass = 'QuickDRY\Connectors\mssql\MSSQL_A';
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

        /* @var MSSQL_Core $DatabaseClass */
        $DatabaseClass::SetDatabase($this->Database);

        $this->Tables = $DatabaseClass::GetTables();

        $this->CreateDirectories();
    }

    /**
     * @return void
     */
    public function DumpSchema(): void
    {
        $DatabaseClass = $this->DatabaseClass;

        if (!is_dir('_Schema')) {
            mkdir('_Schema');
        }

        $BaseFolder = '_Schema/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix);

        if (!is_dir($BaseFolder)) {
            mkdir($BaseFolder);
        }

        /* @var MSSQL_Definition[] $definitions */
        if (!method_exists($DatabaseClass, 'GetDefinitions')) {
            exit($DatabaseClass . '::GetDefintions');
        }

        $definitions = $DatabaseClass::GetDefinitions();
        if ($definitions) {
            foreach ($definitions as $definition) {
                Log::Insert('Definition: ' . $definition->object_name);
                $dest = $BaseFolder . '/_' . $definition->type_desc;
                if (!is_dir($dest)) {
                    mkdir($dest);
                }
                $fp = fopen($dest . '/' . $definition->object_name . '.txt', 'w');
                fwrite($fp, $definition->definition);
                fclose($fp);

            }
        }
    }

    /**
     * @return array
     */
    public function GenerateDatabaseClass(): array
    {
        $DatabaseClass = $this->DatabaseClass;
        $class_name = $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix);

        /* @var MSSQL_Trigger[] $triggers */
        /* // hold off on this
        $triggers = $DatabaseClass::GetTriggers();

        $dest = $this->CommonClassFolder . '/triggers';
        if (!is_dir($dest)) {
            mkdir($dest);
        }
        foreach ($triggers as $trigger) {
            Log::Insert('Trigger: ' . $trigger->name, true);

            $temp = $trigger->definition;
            $trigger->definition = ''; // clear out for the JSON file
            $fp = fopen($dest . '/' . $trigger->name . '.json', 'w');
            fwrite($fp, json_encode($trigger->ToArray(true), JSON_PRETTY_PRINT));
            fclose($fp);

            $trigger->definition = $temp; // store it as given in a txt file
            $fp = fopen($dest . '/' . $trigger->name . '.txt', 'w');
            fwrite($fp, $trigger->definition);
            fclose($fp);
        }
        */

        if (!method_exists($DatabaseClass, 'GetStoredProcs')) {
            exit($DatabaseClass . '::GetStoredProcs');
        }

        /* @var MSSQL_StoredProc[] $stored_procs */
        $stored_procs = $DatabaseClass::GetStoredProcs();

        if (!$stored_procs) {
            return [];
        }
        $sp_require = [];
        foreach ($stored_procs as $sp) {
            $sp_class = SQL_Base::StoredProcToClass($this->DatabasePrefix, $sp->SPECIFIC_NAME, true, $this->DatabaseTypePrefix . '_sp');

            Log::Insert('Stored Proc: ' . $sp_class);

            $this->GenerateSPClassFile($sp_class);

            $sp_require['db_' . $sp_class] = 'common/' . $class_name . '/sp_db/db_' . $sp_class . '.php';
            $sp_require[$sp_class] = 'common/' . $class_name . '/sp/' . $sp_class . '.php';

            if (!method_exists($DatabaseClass, 'GetStoredProcParams')) {
                exit("$DatabaseClass::GetStoredProcParams");
            }

            $sp_params = $DatabaseClass::GetStoredProcParams($sp->SPECIFIC_NAME);
            $params = [];
            $sql_params = [];
            $func_params = [];
            $clean_params = [];
            foreach ($sp_params as $param) {
                $clean_param = str_replace('$$', '$', str_replace('#', '_', str_replace('@', '$', $param->Parameter_name)));
                $clean_params[] = $clean_param;
                $sql_param = str_replace('$', '@', $clean_param);
                $func_params[] = $clean_param;
                $sql_params[] = $sql_param . ' -- ' . str_replace('$', '', $clean_param);
                $params[] = '\'' . str_replace('@', '', $sql_param) . '\' => ' . $clean_param;
            }

            $template = file_get_contents(__DIR__ . '/../_templates/sp_db_mssql.txt');
            $vars = [
                'sp_class' => $sp_class,
                'func_params' => implode(', ', $func_params),
                'clean_params' => implode(PHP_EOL . '     * @param  ', $clean_params),
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