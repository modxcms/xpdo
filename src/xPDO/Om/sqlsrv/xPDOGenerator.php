<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Om\sqlsrv;

use PDO;
use xPDO\xPDO;

/**
 * An extension for generating {@link xPDOObject} class and map files for sqlsrv.
 *
 * A sqlsrv-specific extension to an {@link xPDOManager} instance that can
 * generate class stub and meta-data map files from a provided XML schema of a
 * database structure.
 *
 * @package xPDO\Om\sqlsrv
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
    public function writeSchema($schemaFile, $package= '', $baseClass= '', $tablePrefix= '', $restrictPrefix= false) {
        if (empty ($package))
            $package= $this->manager->xpdo->package;
        if (empty ($baseClass))
            $baseClass= 'xPDO\Om\xPDOObject';
        if (empty ($tablePrefix))
            $tablePrefix= $this->manager->xpdo->config[xPDO::OPT_TABLE_PREFIX];
        $xmlContent = array();
        $xmlContent[] = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $xmlContent[] = "<model package=\"{$package}\" baseClass=\"{$baseClass}\" platform=\"sqlsrv\">";
        //read list of tables
        $tableLike= ($tablePrefix && $restrictPrefix);
        if ($tableLike) {
            $tablesStmt= $this->manager->xpdo->query("SELECT * FROM sys.Tables WHERE name LIKE '{$tablePrefix}%' ORDER BY name");
        } else {
            $tablesStmt= $this->manager->xpdo->query("SELECT * FROM sys.Tables ORDER BY name");
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
            $fieldsStmt= $this->manager->xpdo->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '{$tableName}'");
            $fields= $fieldsStmt->fetchAll(PDO::FETCH_ASSOC);
            if ($this->manager->xpdo->getDebug() === true) $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, "Fields for table {$table[0]}: " . print_r($fields, true));
            $cid = 0;
            foreach ($fields as $field) {
                $name = '';
                $type = '';
                $notnull = 0;
                $dflt_value = null;
                $pk = 0;
                extract($field, EXTR_OVERWRITE);
                $Field= $name;
                $PhpType= $this->manager->getPhpType($type);
                $Null= ' null="' . ($notnull ? 'false' : 'true') . '"';
                $Default= $this->getDefault($dflt_value);
                $Extra= '';
                if (!empty($pk)) {
                    if (preg_match('/INT/i', $type)) {
                        if ($baseClass === 'xPDO\Om\xPDOObject' && $Field === 'id') {
                            $extends= 'xPDO\Om\xPDOSimpleObject';
                            continue;
                        } elseif ($cid == 0) {
                            $Extra= ' generated="native"';
                        }
                    }
                    $Key = ' index="pk"';
                } else {
                    $Key = $this->getIndex($field);
                }
                $xmlFields[]= "\t\t<field key=\"{$Field}\" dbtype=\"{$type}\" phptype=\"{$PhpType}\"{$Null}{$Default}{$Key}{$Extra} />";
                $cid++;
            }
            $indicesStmt= $this->manager->xpdo->query("PRAGMA index_list({$table[0]})");
            $indices= $indicesStmt->fetchAll(PDO::FETCH_ASSOC);
            if ($this->manager->xpdo->getDebug() === true) $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, "Indices for table {$table[0]}: " . print_r($indices, true));
            foreach ($indices as $index) {
                $primary = preg_match('/^sqlite_autoindex/', $index['name']) ? 'true' : 'false';
                $unique = !empty($index['unique']) ? 'true' : 'false';
                $xmlIndices[]= "\t\t<index alias=\"{$index['name']}\" name=\"{$index['name']}\" primary=\"{$primary}\" unique=\"{$unique}\">";
                $columnsStmt = $this->manager->xpdo->query("PRAGMA index_info({$index['name']})");
                $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
                if ($this->manager->xpdo->getDebug() === true) $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, "Columns of index {$index['name']}: " . print_r($columns, true));
                foreach ($columns as $column) {
                    $xmlIndices[]= "\t\t\t<column key=\"{$column['name']}\" />";
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
