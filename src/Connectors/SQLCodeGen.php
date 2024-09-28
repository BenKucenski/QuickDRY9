<?php

namespace Bkucenski\Quickdry\Connectors;

use Bkucenski\Quickdry\Connectors\mssql\MSSQL_ForeignKey;
use Bkucenski\Quickdry\Connectors\mssql\MSSQL_TableColumn;
use Bkucenski\Quickdry\Connectors\mysql\MySQL_ForeignKey;
use Bkucenski\Quickdry\Utilities\Log;
use Bkucenski\Quickdry\Utilities\Strings;
use Bkucenski\Quickdry\Utilities\strongType;

/**
 * Class SQLCodeGen
 */
class SQLCodeGen extends strongType
{
    protected string $DestinationFolder;
    protected string $Database;
    protected string $DatabaseConstant;
    protected string $DatabasePrefix;
    protected string $UserClass;
    protected string $UserVar;
    protected string $UserIdColumn;
    protected string $MasterPage;
    protected array $Tables;
    protected int $LowerCaseTables;
    protected int $UseFKColumnName;
    protected string $DatabaseTypePrefix;
    protected string $DatabaseClass;
    protected string $DatabaseType;
    protected int $GenerateJSON;

    protected string $CommonFolder;
    protected string $CommonClassFolder;
    protected string $CommonClassDBFolder;
    protected string $CommonClassSPFolder;
    protected string $CommonClassSPDBFolder;
    protected string $PagesBaseJSONFolder;
    protected string $PagesJSONFolder;
    protected string $PagesJSONControlsFolder;

    protected string $PagesBaseManageFolder;
    protected string $PagesManageFolder;
    protected string $PagesPHPUnitFolder;

    /**
     * @return void
     */
    protected function CreateDirectories(): void
    {
        $this->CommonFolder = $this->DestinationFolder . '/common';
        $this->CommonClassFolder = $this->DestinationFolder . '/common/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix);
        $this->CommonClassDBFolder = $this->DestinationFolder . '/common/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '/db';

        $this->CommonClassSPFolder = $this->DestinationFolder . '/common/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '/sp';
        $this->CommonClassSPDBFolder = $this->DestinationFolder . '/common/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix) . '/sp_db';

        $this->PagesPHPUnitFolder = $this->DestinationFolder . '/phpunit';

        $this->PagesBaseJSONFolder = $this->DestinationFolder . '/pages/json';
        $this->PagesBaseManageFolder = $this->DestinationFolder . '/pages/manage';

        if (!is_dir($this->PagesBaseJSONFolder)) {
            mkdir($this->PagesBaseJSONFolder);
        }

        if (!is_dir($this->PagesBaseManageFolder)) {
            mkdir($this->PagesBaseManageFolder);
        }

        if (!is_dir($this->CommonFolder)) {
            mkdir($this->CommonFolder);
        }

        if (!is_dir($this->CommonClassFolder)) {
            mkdir($this->CommonClassFolder);
        }

        if (!is_dir($this->CommonClassDBFolder)) {
            mkdir($this->CommonClassDBFolder);
        }

        if (!is_dir($this->CommonClassSPFolder)) {
            mkdir($this->CommonClassSPFolder);
        }

        if (!is_dir($this->CommonClassSPDBFolder)) {
            mkdir($this->CommonClassSPDBFolder);
        }

        if (!is_dir($this->PagesPHPUnitFolder)) {
            mkdir($this->PagesPHPUnitFolder);
        }
    }

    /**
     * @param string $col_type
     * @param string $DatabaseType
     * @return string
     */
    public static function ColumnTypeToProperty(
        string $col_type,
        string $DatabaseType
    ): string
    {
        switch (strtolower($col_type)) {
            case 'varchar':
            case 'char':
            case 'nchar':
            case 'keyword':
            case 'text':
            case 'nvarchar':
            case 'image':
            case 'uniqueidentifier':
            case 'longtext':
            case 'longblob':
                return 'string';

            case 'tinyint unsigned':
            case 'bigint unsigned':
            case 'int unsigned zerofill':
            case 'long':
            case 'bit':
            case 'bigint':
            case 'smallint':
            case 'tinyint':
            case 'numeric':
            case 'int unsigned':
                return 'int';

            case 'money':
            case 'decimal':
                return 'float';

            case 'smalldatetime':
            case 'datetime':
            case 'date':
                if($DatabaseType === 'mysql') {
                    return 'string';
                }
                return 'DateTime';
        }
        return $col_type;
    }

    /**
     * @param $sp_class
     */
    public function GenerateSPClassFile($sp_class): void
    {
        $template = file_get_contents(__DIR__ . '/_templates/sp.txt');
        $vars = [
            'sp_class' => $sp_class,
        ];

        $include_php = $template;
        foreach ($vars as $name => $v) {
            $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
        }

        self::SaveFile($this->CommonClassSPFolder, $sp_class, $include_php);
    }

    /**
     * @return array|null
     */
    public function GenerateDatabaseClass(): ?array
    {
        return null;
    }

    /**
     * @return void
     */
    public function GenerateClasses(): void
    {
        $this->GenerateDatabaseClass();

        foreach ($this->Tables as $table_name) {
            Log::Insert($table_name);

            $DatabaseClass = $this->DatabaseClass;

            if (!method_exists($DatabaseClass, 'GetTableColumns')) {
                exit("$DatabaseClass::GetTableColumns");
            }

            $columns = $DatabaseClass::GetTableColumns($table_name);
            $this->GenerateClass($table_name, $columns);
        }
    }

    /**
     * @param $table_name
     * @param $cols
     * @return string
     */
    public function GenerateClass($table_name, $cols): string
    {
        switch($this->DatabaseTypePrefix) {
            case 'ms':
                $this->DatabaseType = 'mssql';
                break;
            case 'my':
                $this->DatabaseType = 'mysql';
                break;
        }

        $DatabaseClass = $this->DatabaseClass;
        $class_props = [];

        $c_name = SQL_Base::TableToClass($this->DatabasePrefix, $table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix);
        Log::Insert($c_name);

        if (!method_exists($DatabaseClass, 'GetUniqueKeys')) {
            exit("$DatabaseClass::GetUniqueKeys");
        }
        if (!method_exists($DatabaseClass, 'GetPrimaryKey')) {
            exit("$DatabaseClass::GetPrimaryKey");
        }
        if (!method_exists($DatabaseClass, 'GetIndexes')) {
            exit("$DatabaseClass::GetIndexes");
        }


        $props = '';
        $unique = $DatabaseClass::GetUniqueKeys($table_name);
        $primary = $DatabaseClass::GetPrimaryKey($table_name);
        $indexes = $DatabaseClass::GetIndexes($table_name);

        $aliases = [];

        $HasUserLink = false;

        foreach ($cols as $col) {
            /* @var MSSQL_TableColumn $col */ // these are the same for MySQL and MSSQL, only claim it's one to help with code completion
            if ($col->field !== $col->field_alias) {
                $aliases[] = $col;
            }
            $class_props[] = ' * @property ' . SQLCodeGen::ColumnTypeToProperty(preg_replace('/\(.*?\)/si', '', $col->type), $this->DatabaseType) . ' ' . $col->field_alias;
            $props .= "'" . $col->field . "' => ['type' => '" . str_replace('\'', '\\\'', $col->type) . "', 'is_nullable' => " . ($col->null ? 'true' : 'false') . ", 'display' => '" . SQLCodeGen::FieldToDisplay($col->field) . "'],\r\n\t\t";
            if ($col->field === 'user_id') {
                $HasUserLink = true;
            }
        }


        if (!method_exists($DatabaseClass, 'GetForeignKeys')) {
            exit("$DatabaseClass::GetForeignKeys");
        }

        /* @var MSSQL_ForeignKey[]|MySQL_ForeignKey[] $refs */
        $refs = $DatabaseClass::GetForeignKeys($table_name);
        $gets = [];
        $sets = [];

        $foreign_key_props = [];

        $seens_vars = [];
        $db_use = [];
        $db_use[] = 'use ' . $this->DatabaseClass . ';';

        foreach ($aliases as $alias) {
            /* @var MSSQL_TableColumn $alias */
            $gets[] = "
            case '" . $alias->field_alias . "':
                return \$this->GetProperty('" . $alias->field . "');
            ";

            $sets[] = "
            case '" . $alias->field_alias . "':
                return \$this->SetProperty('" . $alias->field . "', \$value);
            ";

        }

        foreach ($refs as $fk) {
            if (is_array($fk->column_name)) {
                $column_name = $this->UseFKColumnName ? '_' . implode('_', $fk->column_name) : '';
            } else {
                $column_name = $this->UseFKColumnName ? '_' . $fk->column_name : '';
            }
            $var = 'fk_' . preg_replace('/[^a-z0-9]/si', '_', str_replace(' ', '_', $fk->foreign_table_name) . $column_name);

            if (in_array($var, $seens_vars)) {
                Log::Insert(['duplicate FK', $fk]);
                continue;
            }
            $seens_vars[] = $var;

            $fk_class = SQL_Base::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix);

            $namespace = strtolower($this->DatabaseConstant ? $this->DatabaseTypePrefix . '_'  . $this->DatabaseConstant : $this->DatabaseTypePrefix . '_'  . $this->Database);
            $db_use [] = 'use common\\' . $namespace . '\\' . $fk_class . ';';

            $class_props[] = ' * @property ' . $fk_class . ' ' . $var;
            $foreign_key_props[] = 'protected ?' . $fk_class . ' $_' . $var . ' = null;';

            if (is_array($fk->column_name)) {
                $isset = [];
                $get_params = [];
                foreach ($fk->column_name as $i => $col) {
                    $isset[] = '$this->' . $col;
                    $get_params[] = "'" . $fk->foreign_column_name[$i] . "'=>\$this->" . $col;
                }

                $gets[] = "
            case '$var':
                if(!isset(\$this->_$var) && " . implode(' && ', $isset) . ") {
                    \$this->_$var = " . SQL_Base::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix) . '::Get([' . implode(', ', $get_params) . "]);
                }
                return \$this->_$var;
            ";
            } else {
                $gets[] = "
            case '$var':
                if(!isset(\$this->_$var) && \$this->" . $fk->column_name . ") {
                    \$this->_$var = " . SQL_Base::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix) . "::Get(['" . $fk->foreign_column_name . "'=>\$this->" . $fk->column_name . "]);
                }
                return \$this->_$var;
            ";
            }
        }

        if (!method_exists($DatabaseClass, 'GetLinkedTables')) {
            exit("$DatabaseClass::GetLinkedTables");
        }

        /* @var MSSQL_ForeignKey[]|MySQL_ForeignKey[] $refs */
        $refs = $DatabaseClass::GetLinkedTables($table_name);

        $fk_counts = [];
        foreach ($refs as $fk) {
            if (is_array($fk->column_name)) {
                $column_name = $this->UseFKColumnName ? '_' . str_ireplace('_ID', '', implode('_', $fk->column_name)) : '';
            } else {
                $column_name = $this->UseFKColumnName ? '_' . str_ireplace('_ID', '', $fk->column_name) : '';
            }
            $var = preg_replace('/[^a-z0-9]/i', '_', str_replace(' ', '_', $fk->foreign_table_name) . $column_name);
            $var = 'fk_' . $var;


            if (in_array($var, $seens_vars)) {
                Log::Insert(['duplicate FK', $fk]);
                continue;
            }
            $seens_vars[] = $var;

            $fk_class = SQL_Base::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix);
            $namespace = strtolower($this->DatabaseConstant ? $this->DatabaseTypePrefix . '_'  . $this->DatabaseConstant : $this->DatabaseTypePrefix . '_'  . $this->Database);
            $db_use [] = 'use common\\' . $namespace . '\\' . $fk_class . ';';


            $class_props[] = ' * @property ' . $fk_class . '[] ' . $var;
            $class_props[] = ' * @property ' . $fk_class . '[] _' . $var;
            $class_props[] = ' * @property int ' . $var . 'Count';


            $foreign_key_props[] = 'protected ?array $_' . $var . ' = null;';
            $foreign_key_props[] = 'protected ?int $_' . $var . 'Count = null;';

            if (is_array($fk->column_name)) {
                $isset = [];
                $get_params = [];
                foreach ($fk->column_name as $i => $col) {
                    $isset[] = '$this->' . $col;
                    $get_params[] = "'" . $fk->foreign_column_name[$i] . "'=>\$this->" . $col;
                }
                $fk_counts [] = $var . 'Count';

                $gets[] = "
            case '$var':
                if(is_null(\$this->_$var) && " . implode(' && ', $isset) . ") {
                    \$this->_$var = " . SQL_Base::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix) . '::GetAll([' . implode(', ', $get_params) . "]);
                }
                return \$this->_$var;

            case '{$var}Count':
                if(is_null(\$this->_{$var}Count) && " . implode(' && ', $isset) . ") {
                    \$this->_{$var}Count = " . SQL_Base::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix) . '::GetCount([' . implode(', ', $get_params) . "]);
                }
                return \$this->_{$var}Count;
            ";

            } else {
                $fk_counts [] = $var . 'Count';
                $gets[] = "
            case '$var':
                if(is_null(\$this->_$var) && \$this->" . $fk->column_name . ") {
                    \$this->_$var = " . SQL_Base::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix) . "::GetAll(['" . $fk->foreign_column_name . "'=>\$this->" . $fk->column_name . "]);
                }
                return \$this->_$var;

            case '{$var}Count':
                if(is_null(\$this->_{$var}Count) && \$this->" . $fk->column_name . ") {
                    \$this->_{$var}Count = " . SQL_Base::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix) . "::GetCount(['" . $fk->foreign_column_name . "'=>\$this->" . $fk->column_name . "]);
                }
                return \$this->_{$var}Count;
            ";
            }
        }

        $unique_code = '';

        foreach ($unique as $columns) {
            $unique_code .= '          [' . (sizeof($columns) ? '\'' . implode('\',\'', $columns) . '\'' : '') . '],' . PHP_EOL;
        }

        $indexes_code = '';
        foreach ($indexes as $key => $columns) {
            $indexes_code .= '        \'' . $key . '\' => [' . (sizeof($columns) ? '\'' . implode('\',\'', $columns) . '\'' : '') . '],' . PHP_EOL;
        }

        $template = file_get_contents(__DIR__ . '/_templates/class_db.txt');
        $vars = [
            'namespace' => 'common\\' . strtolower($this->DatabaseTypePrefix . '_' . $this->DatabasePrefix) . '\\db',
            'use' => implode(PHP_EOL, array_unique($db_use)),
            'c_name' => $c_name,
            'class_props' => implode("\r\n", $class_props),
            'DatabaseClass' => $DatabaseClass,
            'DatabaseClassName' => str_replace('QuickDRY\\Connectors\\' . $this->DatabaseType . '\\', '', $DatabaseClass),
            'primary' => (sizeof($primary) ? '[\'' . implode('\',\'', $primary) . '\']' : '[]'),
            'unique' => $unique_code,
            'indexes' => $indexes_code,
            'prop_definitions' => $props,
            'database' => (!$this->DatabaseConstant ? '\'' . $this->Database . '\'' : $this->DatabaseConstant),
            'table_name' => $table_name,
            'DatabasePrefix' => (!$this->DatabaseConstant ? $this->Database : $this->DatabaseConstant),
            'DatabaseTypePrefix' => $this->DatabaseTypePrefix,
            'LowerCaseTable' => ($this->LowerCaseTables ? 1 : 0),
            'foreign_key_props' => implode("\r\n\t", $foreign_key_props),
            'gets' => sizeof($gets) ? '
        switch($name)
        {
            ' . implode("\r\n        ", $gets) . '
        }' : '',
            'sets' => sizeof($sets) ? '
        switch($name)
        {
            ' . implode("\r\n        ", $sets) . '
        }' : '',
            'UserClass' => $this->UserClass,
            'IsReferenced' => (sizeof($fk_counts) == 0 ? 'false' : '!($this->' . implode(' + $this->', $fk_counts) . ' == 0)'),
            'HasUserLink' => $HasUserLink ? '
        if(!$this->id) {
            return true;
        }

        if($this->user_id == $user->id) {
            return true;
        }
      ' : '',
        ];

        $include_php = $template;
        foreach ($vars as $name => $v) {
            $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
        }

        $fp = fopen($this->CommonClassDBFolder . '/db_' . $c_name . '.php', 'w');
        fwrite($fp, $include_php);
        fclose($fp);

        $template = file_get_contents(__DIR__ . '/_templates/class.txt');
        $vars = [
            'namespace' => 'common\\' . strtolower($this->DatabaseTypePrefix . '_' . $this->DatabasePrefix),
            'use' => 'use common\\' . strtolower($this->DatabaseTypePrefix . '_' . $this->DatabasePrefix) . '\\db\\db_' . $c_name . ';',
            'c_name' => $c_name,
            'class_props' => implode("\r\n", $class_props),
            'HasUserLink' => $HasUserLink ? '
        global $Web;
        if($this->id) {
            if($this->user_id !== $Web->CurrentUser->id) {
                $res[\'error\'] = [\'No Permission\'];
                return $res;
            }
        } else {
            $this->user_id = $Web->CurrentUser->id;
        }
' : '',
        ];

        $include_php = $template;
        foreach ($vars as $name => $v) {
            $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
        }

        self::SaveFile($this->CommonClassFolder, $c_name, $include_php);

        return $c_name;
    }

    public static array $CacheFileLists = [];

    /**
     * @param string $base_folder
     * @param string $file_name
     * @param $data
     * @param bool $force
     */
    public static function SaveFile(string $base_folder, string $file_name, $data, bool $force = false): void
    {
        $file = $base_folder . '/' . $file_name . '.php';
        if (!$force) {
            if (!isset(self::$CacheFileLists[$base_folder])) {
                self::$CacheFileLists[$base_folder] = scandir($base_folder);
            }
            $files = self::$CacheFileLists[$base_folder];
            $file_exists = false;
            foreach ($files as $fname) {
                if (strcasecmp($fname, $file_name . '.php') == 0) {
                    $file_exists = true;
                    if (!(strcmp($fname, $file_name . '.php') == 0)) {
                        rename($base_folder . '/' . $file_name . '.php', $file);
                    }
                    break;
                }
            }


            if (!$file_exists) {
                $fp = fopen($file, 'w');
                fwrite($fp, $data);
                fclose($fp);
            }
        } else {
            $fp = fopen($file, 'w');
            fwrite($fp, $data);
            fclose($fp);
        }
    }

    /**
     * @return void
     */
    public function GenerateJSON(): void
    {
        if (!$this->GenerateJSON) {
            return;
        }


        $DatabaseClass = $this->DatabaseClass;

        foreach ($this->Tables as $table_name) {
            Log::Insert($table_name);

            $this->PagesJSONFolder = $this->PagesBaseJSONFolder . '/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix);

            if (!is_dir($this->PagesJSONFolder)) {
                mkdir($this->PagesJSONFolder);
            }

            if (!method_exists($DatabaseClass, 'TableToNiceName')) {
                exit("$DatabaseClass::TableToNiceName");
            }

            $table_nice_name = $DatabaseClass::TableToNiceName($table_name, $this->LowerCaseTables);

            $this->PagesJSONFolder .= '/' . $table_nice_name;
            if (!is_dir($this->PagesJSONFolder)) {
                mkdir($this->PagesJSONFolder);
            }

            if (!is_dir($this->PagesJSONFolder . '/base')) {
                mkdir($this->PagesJSONFolder . '/base');
            }

            $this->PagesJSONControlsFolder = $this->PagesJSONFolder . '/controls';
            if (!is_dir($this->PagesJSONControlsFolder)) {
                mkdir($this->PagesJSONControlsFolder);
            }


            $this->PagesManageFolder = $this->PagesBaseManageFolder . '/' . $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix);

            if (!is_dir($this->PagesManageFolder)) {
                mkdir($this->PagesManageFolder);
            }

            $this->PagesManageFolder .= '/' . $table_nice_name;
            if (!is_dir($this->PagesManageFolder)) {
                mkdir($this->PagesManageFolder);
            }

            if (!method_exists($DatabaseClass, 'GetTableColumns')) {
                exit("$DatabaseClass::GetTableColumns");
            }

            $columns = $DatabaseClass::GetTableColumns($table_name);
            $this->_GenerateJSON($table_name, $table_nice_name, $columns);
        }
    }

    /**
     * @param $table_name
     * @param $table_nice_name
     * @param $cols
     * @return void
     */
    protected function _GenerateJSON($table_name, $table_nice_name, $cols): void
    {
        $DatabaseClass = $this->DatabaseClass;

        $c_name = SQL_Base::TableToClass($this->DatabasePrefix, $table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix);

        if (!method_exists($DatabaseClass, 'GetPrimaryKey')) {
            exit("$DatabaseClass::GetPrimaryKey");
        }

        // $unique = $DatabaseClass::GetUniqueKeys($table_name);
        $primary = $DatabaseClass::GetPrimaryKey($table_name);

        $this->Add($c_name, $table_name, $cols, $primary, $table_nice_name);
        $this->History($c_name, $table_nice_name, $primary);
        $this->Manage($c_name, $table_nice_name, $primary);

        $this->CRUDClass($c_name, $table_nice_name, $primary);
    }

    /**
     * @param $c_name
     * @param $table_nice_name
     * @param $primary
     * @return void
     */
    protected function CRUDClass($c_name, $table_nice_name, $primary): void
    {
        if (!sizeof($primary)) {
            return;
        }
        $namespace = 'json\\' . $this->DatabaseTypePrefix . '_' . $this->Database;

        $get_params = [];
        $missing_params = [];
        foreach ($primary as $param) {
            $get_params [] = '\'' . $param . '\' => self::$Request->Get(\'' . $param . '\')';
            $missing_params [] = '!self::$Request->Get(\'' . $param . '\')';

        }
        $get_params = implode(', ', $get_params);
        $missing_params = implode(' || ', $missing_params);

        $template = file_get_contents(__DIR__ . '/_templates/crud.txt');
        $vars = [
            'table_nice_name' => $table_nice_name,
            'namespace' => $namespace,
        ];

        $include_php = $template;
        foreach ($vars as $name => $v) {
            $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
        }

        self::SaveFile($this->PagesJSONFolder, $table_nice_name, $include_php);


        $template = file_get_contents(__DIR__ . '/_templates/crud.code.txt');
        $vars = [
            'table_nice_name' => $table_nice_name,
            'namespace' => $namespace,
        ];

        $include_php = $template;
        foreach ($vars as $name => $v) {
            $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
        }

        self::SaveFile($this->PagesJSONFolder, $table_nice_name, $include_php);

        $template = file_get_contents(__DIR__ . '/_templates/crud_base.txt');
        $vars = [
            'namespace_c_name' => 'common\\' . $this->DatabaseTypePrefix . '_' . $this->Database . '\\' . $c_name,
            'c_name' => $c_name,
            'get_params' => $get_params,
            'table_nice_name' => $table_nice_name,
            'missing_params' => $missing_params,
            'namespace' => $namespace,
        ];

        $include_php = $template;
        foreach ($vars as $name => $v) {
            $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
        }

        self::SaveFile($this->PagesJSONFolder . '/base', $table_nice_name . 'Base', $include_php, true);
    }

    /**
     * @param string $c_name
     * @param string $table_name
     * @param array $cols
     * @param array $primary
     * @param string $table_nice_name
     * @return void
     */
    protected function Add(string $c_name, string $table_name, array $cols, array $primary, string $table_nice_name): void
    {
        if (!sizeof($cols)) {
            return;
        }

        if (!sizeof($primary)) {
            return;
        }

        $DatabaseClass = $this->DatabaseClass;

        if (!method_exists($DatabaseClass, 'GetForeignKeys')) {
            exit("$DatabaseClass::GetForeignKeys");
        }

        $res = $DatabaseClass::GetForeignKeys($table_name);
        $refs = [];

        foreach ($res as $fk) {
            if (!is_array($fk->column_name)) {
                /* @var MSSQL_ForeignKey $fk */
                $refs[(string)$fk->column_name] = SQL_Base::TableToClass($this->DatabasePrefix, $fk->foreign_table_name, $this->LowerCaseTables, $this->DatabaseTypePrefix);
            }
        }

        $colors = '';
        $colors_set = '';
        $form = '';

        foreach ($primary as $col) {
            $form .= '<input type="hidden" name="' . $col . '" id="' . $c_name . '_' . $col . '" />' . "\r\n";
        }

        $form .= '
<table class="dialog_form">
';


        foreach ($cols as $col)
            if (!in_array($col->field, $primary)) {
                if ($col->field === 'user_id') {
                    continue;
                }
                if (str_ends_with($col->field, '_by_id')) {
                    continue;
                }
                if (str_ends_with($col->field, '_at')) {
                    continue;
                }
                if (str_ends_with($col->field, '_file')) {
                    continue;
                }

                if (isset($refs[$col->field])) {
                    if ($refs[$col->field] === 'ColorClass') {
                        $colors .= '
	$(\'#' . $c_name . '_' . $col->field . '_selected\').html(\'Select One...\');
	$(\'#' . $c_name . '_' . $col->field . '_selected\').css({\'background-color\' : \'#ffffff\'});
				';

                        $color_var = str_replace('_id', '', $col->field);
                        $colors_set .= '
	if(data.serialized.' . $color_var . ') {
		$(\'#' . $c_name . '_' . $col->field . '_selected\').html(\'\');
		$(\'#' . $c_name . '_' . $col->field . '_selected\').css({\'background-color\' : \'#\' + data.serialized.' . $color_var . '});
	}
				';
                    }

                    $form .= '<tr><td class="name">' . SQLCodeGen::FieldToDisplay($col->field) . '</td><td class="field"><?php echo ' . $refs[$col->field] . '::Select(null, new ElementID(\'' . $c_name . '_' . $col->field . '\', \'' . $col->field . '\')); ?></td></tr>' . "\r\n";

                } else
                    switch ($col->type) {
                        case 'text':
                            $form .= '<tr><td class="name">' . SQLCodeGen::FieldToDisplay($col->field) . '</td><td class="field"><textarea class="form-control" name="' . $col->field . '" id="' . $c_name . '_' . $col->field . '"></textarea></td></tr>' . "\r\n";
                            break;

                        case 'bit':
                        case 'tinyint(1)':
                        case 'tinyint':
                            $elem = $c_name . '_' . $col->field;

                            $form .= '<tr><td class="name">' . SQLCodeGen::FieldToDisplay($col->field) . '</td><td class="field">
					<input type="checkbox" id="' . $elem . '" onclick="$(\'#' . $elem . '_hidden\').val(this.checked ? 1 : 0);" />
					<input type="hidden" name="' . $col->field . '" id="' . $elem . '_hidden" value="0" />
					</td></tr>' . "\r\n";
                            break;

                        case 'datetime':
                        case 'timestamp':
                            $form .= '<tr><td class="name">' . SQLCodeGen::FieldToDisplay($col->field) . '</td><td class="field"><input class="time-picker form-control" type="text" name="' . $col->field . '" id="' . $c_name . '_' . $col->field . '" /></td></tr>' . "\r\n";
                            break;

                        case 'date':
                            $form .= '<tr><td class="name">' . SQLCodeGen::FieldToDisplay($col->field) . '</td><td class="field"><input class="form-control" type="date" name="' . $col->field . '" id="' . $c_name . '_' . $col->field . '" /></td></tr>' . "\r\n";
                            break;

                        case 'varchar(6)':
                            $form .= '<tr><td class="name">' . SQLCodeGen::FieldToDisplay($col->field) . '</td><td class="field"><input class="color form-control" type="text" name="' . $col->field . '" id="' . $c_name . '_' . $col->field . '" /></td></tr>' . "\r\n";
                            break;

                        default:
                            $form .= '<tr><td class="name">' . SQLCodeGen::FieldToDisplay($col->field) . '</td><td class="field"><input class="form-control" type="text" name="' . $col->field . '" id="' . $c_name . '_' . $col->field . '" /></td></tr>' . "\r\n";
                    }
            }

        $form .= '</table>';

        $vars = [
            'c_name' => $c_name,
            'primary' => $primary[0],
            'table_nice_name' => $table_nice_name,
            'JSONFolder' => $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix),
            'form' => $form,
        ];

        $template = file_get_contents(__DIR__ . '/_templates/add.txt');

        $include_php = $template;
        foreach ($vars as $name => $v) {
            $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
        }

        $fp = fopen($this->PagesJSONControlsFolder . '/add.php', 'w');
        fwrite($fp, $include_php);
        fclose($fp);


        $template = file_get_contents(__DIR__ . '/_templates/add.js.txt');

        $include_php = $template;
        foreach ($vars as $name => $v) {
            $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
        }

        $fp = fopen($this->PagesJSONControlsFolder . '/add.js', 'w');
        fwrite($fp, $include_php);
        fclose($fp);


    }

    /**
     * @param string $c_name
     * @param string $table_nice_name
     * @param array $primary
     */
    protected function History(string $c_name, string $table_nice_name, array $primary): void
    {
        if (!sizeof($primary)) {
            return;
        }

        $vars = [
            'c_name' => $c_name,
            'primary' => $primary[0],
            'table_nice_name' => $table_nice_name,
            'JSONFolder' => $this->DatabaseTypePrefix . '_' . strtolower($this->DatabasePrefix),
            'ClassName' => Strings::CapsToSpaces(str_replace('Class', '', $c_name)),
        ];

        $template = file_get_contents(__DIR__ . '/_templates/history.txt');

        $include_php = $template;
        foreach ($vars as $name => $v) {
            $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
        }

        $fp = fopen($this->PagesJSONControlsFolder . '/history.php', 'w');
        fwrite($fp, $include_php);
        fclose($fp);


        $template = file_get_contents(__DIR__ . '/_templates/history.js.txt');

        $include_php = $template;
        foreach ($vars as $name => $v) {
            $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
        }

        $fp = fopen($this->PagesJSONControlsFolder . '/history.js', 'w');
        fwrite($fp, $include_php);
        fclose($fp);
    }

    /**
     * @param $c_name
     * @param $table_nice_name
     * @param $primary
     */
    protected function Manage($c_name, $table_nice_name, $primary): void
    {
        if (!sizeof($primary)) {
            return;
        }

        $namespace = 'manage\\' . $this->DatabaseTypePrefix . '_' . $this->Database;

        $template = file_get_contents(__DIR__ . '/_templates/manage.txt');
        $vars = [
            'c_name' => $c_name,
            'table_nice_name' => $table_nice_name,
            'namespace' => $namespace,
            'DestinationFolder' => str_replace($this->DestinationFolder . '/', '', $this->PagesJSONFolder),
        ];

        $include_php = $template;
        foreach ($vars as $name => $v) {
            $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
        }

        $fp = fopen($this->PagesManageFolder . '/' . $table_nice_name . '.html.php', 'w');
        fwrite($fp, $include_php);
        fclose($fp);

        $template = file_get_contents(__DIR__ . '/_templates/manage.code.txt');
        $vars = [
            'namespace_c_name' => 'common\\' . $this->DatabaseTypePrefix . '_' . $this->Database . '\\' . $c_name,
            'c_name' => $c_name,
            'namespace' => $namespace,
            'table_nice_name' => $table_nice_name,
        ];

        $include_php = $template;
        foreach ($vars as $name => $v) {
            $include_php = str_replace('[[' . $name . ']]', $v, $include_php);
        }

        $fp = fopen($this->PagesManageFolder . '/' . $table_nice_name . '.php', 'w');
        fwrite($fp, $include_php);
        fclose($fp);
    }

    /**
     * @param $field
     *
     * @return string
     */
    public static function FieldToDisplay($field): string
    {
        $t = ucwords(implode(' ', explode('_', $field)));
        $t = str_replace(' ', '', $t);
        if (strcasecmp(substr($t, -2), 'id') == 0)
            $t = substr($t, 0, strlen($t) - 2);
        return Strings::CapsToSpaces($t);
    }
}