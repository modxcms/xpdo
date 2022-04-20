<?php
/*
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Om\pgsql;


use PDO;
use xPDO\xPDO;

class xPDOGenerator extends \xPDO\Om\xPDOGenerator
{
    /**
     * @inheritDoc
     */
    public function getIndex($index)
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function compile($path = '')
    {
        return false;
    }

    public function writeSchema($schemaFile, $package = '', $baseClass = '', $tablePrefix = '', $restrictPrefix = false)
    {
        if (empty ($package))
            $package= $this->manager->xpdo->package;
        if (empty ($baseClass))
            $baseClass= 'xPDO\Om\xPDOObject';
        if (empty ($tablePrefix))
            $tablePrefix= $this->manager->xpdo->config[xPDO::OPT_TABLE_PREFIX];
        $schemaVersion = xPDO::SCHEMA_VERSION;
        $xmlContent = array();
        $xmlContent[] = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $xmlContent[] = "<model package=\"{$package}\" baseClass=\"{$baseClass}\" platform=\"pgsql\" version=\"{$schemaVersion}\">";
        //read list of tables
        $tableLike= ($tablePrefix && $restrictPrefix);
        if ($tableLike) {
            $tablesStmt= $this->manager->xpdo->query("SELECT relname FROM pg_class WHERE relname LIKE '{$tableLike}' AND relkind = 'r'");
        } else {
            $tablesStmt= $this->manager->xpdo->query("SELECT relname FROM pg_class WHERE relname !~ '^(pg_|sql_)' AND relkind = 'r'");
        }
        $tables= $tablesStmt->fetchAll(PDO::FETCH_NUM);
        if ($this->manager->xpdo->getDebug() === true) $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, print_r($tables, true));
        foreach ($tables as $table) {
            $xmlObject= array();
            $xmlFields= array();
            $xmlIndices= array();
            if (!$tableName= $this->getTableName($table[0], $tablePrefix, $restrictPrefix)) {
                continue;
            }
            $class= $this->getClassName($tableName);
            $extends= $baseClass;
            $fieldsStmt= $this->manager->xpdo->query("SELECT * FROM information_schema.columns where table_name = '{$table[0]}'");
            $fields= $fieldsStmt->fetchAll(PDO::FETCH_ASSOC);
            if ($this->manager->xpdo->getDebug() === true) $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, "Fields for table {$table[0]}: " . print_r($fields, true));
            $cid = 0;
            foreach ($fields as $field) {
                $column_name = '';
                $data_type = '';
                $nullable = 0;
                $data_default = null;
                $precision = " precision=\"%s\"";
                $Default = null;
                extract($field, EXTR_OVERWRITE);
                $Field= $column_name;
                $DataType = preg_replace('/\(\d\)$/i', '', $udt_name);

                if (preg_match('/INT/i', $DataType)) {
                    if (preg_match('/nextval/i', $column_default)) {
                        $DataType = 'SERIAL'; //convert integers with sequence to SERIAL to ease the sequencing
                    } else {
                        $DataType = $data_type;
                    }
                }

                $PhpType= $this->manager->xpdo->driver->getPhpType($DataType);

                $Null= ' null="' . (($is_nullable == 'YES') ? 'true' : 'false') . '"';
                if ($PhpType == 'string') {
                    $Default = $this->getDefault(substr($column_default, strpos($column_default, "'"), strpos($column_default, "'", 1)));
                } else {
                    $Default= $this->getDefault($column_default);
                }
                // Precision
                switch ($PhpType) {
                    case 'string' :
                        $precision = sprintf($precision, (string)$character_maximum_length);
                        break;
                    case 'float' :
                        $precision = sprintf($precision, $numeric_precision . "," . $numeric_scale);
                        break;
                    default :
                        $precision = ' precision=""';
                        break;
                }


                if ($baseClass === 'xPDO\Om\xPDOObject' && $Field === 'id') {
                    $extends= 'xPDO\Om\xPDOSimpleObject';
                    continue;
                }
                $xmlFields[]= "\t\t<field key=\"{$Field}\" dbtype=\"{$DataType}\" phptype=\"{$PhpType}\"{$Null}{$Default}{$precision}/>";
                $cid++;
            }
            $indicesStmt= $this->manager->xpdo->query("SELECT constraint_name, constraint_type FROM information_schema.table_constraints WHERE table_name = '{$table[0]}' AND constraint_type != 'CHECK';");
            $indices= $indicesStmt->fetchAll(PDO::FETCH_ASSOC);
            if ($this->manager->xpdo->getDebug() === true) $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, "Indices for table {$table[0]}: " . print_r($indices, true));
            foreach ($indices as $index) {
                $primary = preg_match('/_PRIMARY$/i', $index['constraint_name']) ? 'true' : 'false';
                $unique = preg_match('/_UNIQUE$/i', $index['constraint_name']) ? 'true' : 'false';
                $indexName = stristr($index['constraint_name'], $table[0] . "_") ? str_ireplace($table[0]. '_', '', $index['constraint_name']) : $index['constraint_name'];
                $constType = substr($index['constraint_type'], 0, stripos($index['constraint_type'], " "));

                $xmlIndices[]= "\t\t<index alias=\"{$indexName}\" name=\"{$indexName}\" primary=\"{$primary}\" unique=\"{$unique}\" type=\"{$constType}\">";

                $columnsStmt = $this->manager->xpdo->query("SELECT * from information_schema.key_column_usage WHERE constraint_name = '{$index['constraint_name']}'");

                $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
                if ($this->manager->xpdo->getDebug() === true) $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, "Columns of index {$index['constraint_name']}: " . print_r($columns, true));
                foreach ($columns as $column) {
                    $xmlIndices[]= "\t\t\t<column key=\"{$column['column_name']}\" />";
                }
                $xmlIndices[]= "\t\t</index>";
            }
            $xmlObject[] = "\t<object class=\"{$class}\" table=\"{$tableName}\" extends=\"{$extends}\">";
            $xmlObject[] = implode("\n", $xmlFields);
            if (!empty($xmlIndices)) {
                $xmlObject[] = '';
                $xmlObject[] = implode("\n", $xmlIndices);
            }
            $xmlObject[] = "\t</object>";
            $xmlContent[] = implode("\n", $xmlObject);
        }
        $xmlContent[] = "</model>";
        if ($this->manager->xpdo->getDebug() === true) {
            $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, implode("\n", $xmlContent));
        }
        $file= fopen($schemaFile, 'wb');
        $written= fwrite($file, implode("\n", $xmlContent));
        fclose($file);
        return true;
    }
}
