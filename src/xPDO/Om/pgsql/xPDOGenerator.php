<?php
/*
 * Copyright 2010-2012 by MODX, LLC.
 *
 * This file is part of xPDO.
 *
 * xPDO is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * xPDO is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * xPDO; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 */

namespace xPDO\Om\pgsql;

use xPDO\xPDO;
use PDO;

/**
 * An extension for generating {@link xPDOObject} class and map files for PostgreSQL.
 *
 * A pgsql-specific extension to an {@link xPDOManager} instance that can
 * generate class stub and meta-data map files from a provided XML schema of a
 * database structure.
 *
 * @package xPDO\Om\pgsql
 */
class xPDOGenerator extends \xPDO\Om\xPDOGenerator {
    public function compile($path = '') {
        return false;
    }

    public function getIndex($index) {
        return '';
    }

    /**
     * Write an xPDO XML Schema from your database.
     *
     * @param string $schemaFile The name (including path) of the schemaFile you
     * want to write.
     * @param string $package Name of the package to generate the classes in.
     * @param string $baseClass The class which all classes in the package will
     * extend; by default this is set to {@link xPDOObject} and any
     * auto_increment fields with the column name 'id' will extend {@link
     * xPDOSimpleObject} automatically.
     * @param string $tablePrefix The table prefix for the current connection,
     * which will be removed from all of the generated class and table names.
     * Specify a prefix when creating a new {@link xPDO} instance to recreate
     * the tables with the same prefix, but still use the generic class names.
     * @param boolean $restrictPrefix Only reverse-engineer tables that have the
     * specified tablePrefix; if tablePrefix is empty, this is ignored.
     * @return boolean True on success, false on failure.
     */
    public function getDefault($value) {
        $return= '';
        $value = trim($value, "' ");
        if ($value !== null) {
            $return= ' default="'.$value.'"';
        }
        return $return;
    }
    public function writeSchema($schemaFile, $package= '', $baseClass= '', $tablePrefix= '', $restrictPrefix= false) {
        if (empty ($package))
            $package= $this->manager->xpdo->package;
        if (empty ($baseClass))
            $baseClass= 'xPDOObject';
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

               
                if ($baseClass === 'xPDOObject' && $Field === 'id') {
                    $extends= 'xPDOSimpleObject';
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
