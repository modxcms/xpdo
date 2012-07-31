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

/**
 * pgsql classes for generating xPDOObject classes and maps from an xPDO schema.
 *
 * @package xpdo
 * @subpackage om.pgsql
 */

/**
 * Include the parent {@link xPDOGenerator} class.
 */
include_once (dirname(dirname(__FILE__)) . '/xpdogenerator.class.php');

/**
 * An extension for generating {@link xPDOObject} class and map files for pgsql.
 *
 * A pgsql-specific extension to an {@link xPDOManager} instance that can
 * generate class stub and meta-data map files from a provided XML schema of a
 * database structure.
 *
 * @package xpdo
 * @subpackage om.pgsql
 */
class xPDOGenerator_pgsql extends xPDOGenerator {
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
        $xmlContent[] = "<model package=\"{$package}\" baseClass=\"{$baseClass}\" platform=\"oci\" version=\"{$schemaVersion}\">";
        //read list of tables
        $tableLike= ($tablePrefix && $restrictPrefix);
        if ($tableLike) {
            $tablesStmt= $this->manager->xpdo->query("SELECT * FROM user_tables WHERE table_name LIKE '{$tablePrefix}%' ORDER BY table_name");
            $tmpSmt = "SELECT * FROM user_tables WHERE table_name LIKE '{$tablePrefix}%' ORDER BY table_name";
        } else {
            $tablesStmt= $this->manager->xpdo->query("SELECT * FROM user_tables ORDER BY table_name");
            $tmpSmt = "SELECT * FROM user_tables ORDER BY table_name";
        }
        echo "<br>" . $tmpSmt . "</br>";
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
            $fieldsStmt= $this->manager->xpdo->query("SELECT * FROM user_tab_cols WHERE table_name = '{$table[0]}'");
            $fields= $fieldsStmt->fetchAll(PDO::FETCH_ASSOC);
            if ($this->manager->xpdo->getDebug() === true) $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, "Fields for table {$table[0]}: " . print_r($fields, true));
            $cid = 0;
            foreach ($fields as $field) {
                $column_name = '';
                $data_type = '';
                $nullable = 0;
                $data_default = null;
                $precision = " precision=\"%s\"";
                
                extract($field, EXTR_OVERWRITE);
                $Field= $COLUMN_NAME;
                $DataType = preg_replace('/\(\d\)$/i', '', $DATA_TYPE);
                $PhpType= $this->manager->xpdo->driver->getPhpType($DataType);
                $Null= ' null="' . (($NULLABLE == 'Y') ? 'true' : 'false') . '"';
                $Default= $this->getDefault($DATA_DEFAULT);
                
                // TODO: Needs refining
                if (!is_null($DATA_PRECISION) && !is_null($DATA_SCALE)) {
                    $precision = sprintf($precision, $DATA_PRECISION . "," . $DATA_SCALE);
                } else if (is_null($DATA_PRECISION) && !is_null($DATA_SCALE)) {
                    if ($PhpType == 'timestamp') {
                        $precision = sprintf($precision, $DATA_SCALE);
                    } else if ($PhpType == 'float') {
                        $precision = sprintf($precision, '*,'.$DATA_SCALE);
                    }
                } else if (!is_null($DATA_PRECISION)) {
                    $precision = sprintf($precision, $DATA_PRECISION);
                } else if ($PhpType == 'string') {
                    $precision = sprintf($precision, $DATA_LENGTH);
                } else {
                    $precision = sprintf($precision, '');
                } 
                if ($baseClass === 'xPDOObject' && $Field === 'id') {
                    $extends= 'xPDOSimpleObject';
                    continue;
                }
                $xmlFields[]= "\t\t<field key=\"{$Field}\" dbtype=\"{$DataType}\" phptype=\"{$PhpType}\"{$Null}{$Default}{$precision}/>";
                $cid++;
            }
            $indicesStmt= $this->manager->xpdo->query("SELECT * FROM user_indexes WHERE TABLE_NAME = '{$table[0]}'");
            $indices= $indicesStmt->fetchAll(PDO::FETCH_ASSOC);
            if ($this->manager->xpdo->getDebug() === true) $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, "Indices for table {$table[0]}: " . print_r($indices, true));
            foreach ($indices as $index) {
                $primary = preg_match('/_PRIMARY$/i', $index['INDEX_NAME']) ? 'true' : 'false';
                $unique = !empty($index['UNIQUENESS']) ? 'true' : 'false';
                $indexName = stristr($index['INDEX_NAME'], $class . "_") ? str_ireplace($class . '_', '', $index['INDEX_NAME']) : $index['INDEX_NAME'];

                $xmlIndices[]= "\t\t<index alias=\"{$indexName}\" name=\"{$index['INDEX_NAME']}\" primary=\"{$primary}\" unique=\"{$unique}\" type=\"{$index['INDEX_TYPE']}\">";
                $columnsStmt = $this->manager->xpdo->query("SELECT * FROM user_ind_columns WHERE index_name = '{$index['INDEX_NAME']}'");

                $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
                if ($this->manager->xpdo->getDebug() === true) $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, "Columns of index {$index['INDEX_NAME']}: " . print_r($columns, true));
                foreach ($columns as $column) {
                    $xmlIndices[]= "\t\t\t<column key=\"{$column['COLUMN_NAME']}\" collation=\"{$column['DESCEND']}\"  />";
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
