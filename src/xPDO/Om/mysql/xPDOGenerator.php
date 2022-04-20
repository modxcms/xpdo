<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Om\mysql;

use xPDO\xPDO;
use PDO;

/**
 * An extension for generating {@link xPDOObject} class and map files for MySQL.
 *
 * A MySQL-specific extension to an {@link xPDOManager} instance that can
 * generate class stub and meta-data map files from a provided XML schema of a
 * database structure.
 *
 * @package xPDO\Om\mysql
 */
class xPDOGenerator extends \xPDO\Om\xPDOGenerator {
    public function compile($path = '') {
        return false;
    }

    public function getIndex($index) {
        switch ($index) {
            case 'PRI':
                $index= 'pk';
                break;

            case 'UNI':
                $index= 'unique';
                break;

            case 'MUL':
                $index= 'index';
                break;

            default:
                break;
        }
        if (!empty ($index)) {
            $index= ' index="' . $index . '"';
        }
        return $index;
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
    public function writeSchema($schemaFile, $package = '', $baseClass = '', $tablePrefix = '', $restrictPrefix = false)
    {
        if (empty($package)) {
            $package = $this->manager->xpdo->package;
        }
        if (empty($baseClass)) {
            $baseClass = 'xPDO\Om\xPDOObject';
        }
        if (empty($tablePrefix)) {
            $tablePrefix = $this->manager->xpdo->config[xPDO::OPT_TABLE_PREFIX];
        }
        $schemaVersion = xPDO::SCHEMA_VERSION;
        $xmlContent = array();
        $xmlContent[] = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $xmlContent[] = "<model package=\"{$package}\" baseClass=\"{$baseClass}\" platform=\"mysql\" defaultEngine=\"InnoDB\" version=\"{$schemaVersion}\">";
        //read list of tables
        $dbname= $this->manager->xpdo->escape($this->manager->xpdo->config['dbname']);
        $tableLike= ($tablePrefix && $restrictPrefix) ? " LIKE '{$tablePrefix}%'" : '';
        $tablesStmt= $this->manager->xpdo->prepare("SHOW TABLES FROM {$dbname}{$tableLike}");
        if (!$tablesStmt) {
            $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Could not query tables from database ' . $dbname);
            return false;
        }

        $tstart = microtime(true);
        $tablesStmt->execute();
        $this->manager->xpdo->queryTime += microtime(true) - $tstart;
        $this->manager->xpdo->executedQueries++;
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
            $fieldsStmt= $this->manager->xpdo->query('SHOW COLUMNS FROM ' . $this->manager->xpdo->escape($table[0]));
            if ($fieldsStmt) {
                $fields= $fieldsStmt->fetchAll(PDO::FETCH_ASSOC);
                if ($this->manager->xpdo->getDebug() === true) $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, print_r($fields, true));
                if (!empty($fields)) {
                    foreach ($fields as $field) {
                        $Field= '';
                        $Type= '';
                        $Null= '';
                        $Key= '';
                        $Default= '';
                        $Extra= '';
                        extract($field, EXTR_OVERWRITE);
                        $Type= xPDO :: escSplit(' ', $Type, "'", 2);
                        $precisionPos= strpos($Type[0], '(');
                        $dbType= $precisionPos? substr($Type[0], 0, $precisionPos): $Type[0];
                        $dbType= strtolower($dbType);
                        $Precision= $precisionPos? substr($Type[0], $precisionPos + 1, strrpos($Type[0], ')') - ($precisionPos + 1)): '';
                        if (!empty ($Precision)) {
                            $Precision= ' precision="' . trim($Precision) . '"';
                        }
                        $attributes= '';
                        if (isset ($Type[1]) && !empty ($Type[1])) {
                            $attributes= ' attributes="' . trim($Type[1]) . '"';
                        }
                        $PhpType= $this->manager->xpdo->driver->getPhpType($dbType);
                        $Null= ' null="' . (($Null === 'NO') ? 'false' : 'true') . '"';
                        $Key= $this->getIndex($Key);
                        $Default= $this->getDefault($Default);
                        if (!empty ($Extra)) {
                            if ($Extra === 'auto_increment') {
                                if ($baseClass === 'xPDO\Om\xPDOObject' && $Field === 'id') {
                                    $extends= 'xPDO\Om\xPDOSimpleObject';
                                    continue;
                                } else {
                                    $Extra= ' generated="native"';
                                }
                            } else {
                                $Extra= ' extra="' . strtolower($Extra) . '"';
                            }
                            $Extra= ' ' . $Extra;
                        }
                        $xmlFields[] = "\t\t<field key=\"{$Field}\" dbtype=\"{$dbType}\"{$Precision}{$attributes} phptype=\"{$PhpType}\"{$Null}{$Default}{$Key}{$Extra} />";
                    }
                } else {
                    $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'No columns were found in table ' .  $table[0]);
                }
            } else {
                $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Error retrieving columns for table ' .  $table[0]);
            }
            $whereClause= ($extends === 'xPDO\Om\xPDOSimpleObject' ? " WHERE `Key_name` != 'PRIMARY'" : '');
            $indexesStmt= $this->manager->xpdo->query('SHOW INDEXES FROM ' . $this->manager->xpdo->escape($table[0]) . $whereClause);
            if ($indexesStmt) {
                $indexes= $indexesStmt->fetchAll(PDO::FETCH_ASSOC);
                if ($this->manager->xpdo->getDebug() === true) $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, "Indices for table {$table[0]}: " . print_r($indexes, true));
                if (!empty($indexes)) {
                    $indices = array();
                    foreach ($indexes as $index) {
                        if (!array_key_exists($index['Key_name'], $indices)) $indices[$index['Key_name']] = array();
                        $indices[$index['Key_name']][$index['Seq_in_index']] = $index;
                    }
                    foreach ($indices as $index) {
                        $xmlIndexCols = array();
                        if ($this->manager->xpdo->getDebug() === true) $this->manager->xpdo->log(xPDO::LOG_LEVEL_DEBUG, "Details of index: " . print_r($index, true));
                        foreach ($index as $columnSeq => $column) {
                            if ($columnSeq == 1) {
                                $keyName = $column['Key_name'];
                                $primary = $keyName == 'PRIMARY' ? 'true' : 'false';
                                $unique = empty($column['Non_unique']) ? 'true' : 'false';
                                $packed = empty($column['Packed']) ? 'false' : 'true';
                                $type = $column['Index_type'];
                            }
                            $null = $column['Null'] == 'YES' ? 'true' : 'false';
                            $xmlIndexCols[]= "\t\t\t<column key=\"{$column['Column_name']}\" length=\"{$column['Sub_part']}\" collation=\"{$column['Collation']}\" null=\"{$null}\" />";
                        }
                        $xmlIndices[]= "\t\t<index alias=\"{$keyName}\" name=\"{$keyName}\" primary=\"{$primary}\" unique=\"{$unique}\" type=\"{$type}\" >";
                        $xmlIndices[]= implode("\n", $xmlIndexCols);
                        $xmlIndices[]= "\t\t</index>";
                    }
                } else {
                    $this->manager->xpdo->log(xPDO::LOG_LEVEL_WARN, 'No indexes were found in table ' .  $table[0]);
                }
            } else {
                $this->manager->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Error getting indexes for table ' .  $table[0]);
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
