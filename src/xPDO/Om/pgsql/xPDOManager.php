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


use Exception;
use PDO;
use PDOException;
use xPDO\xPDO;

/**
 * Provides PostgreSQL data source management for an xPDO instance.
 *
 * These are utility functions that only need to be loaded under special
 * circumstances, such as creating tables, adding indexes, altering table
 * structures, etc.  xPDOManager class implementations are specific to a
 * database driver and this instance is implemented for PostgreSQL.
 *
 * @package xpdo
 * @subpackage om.pgsql
 */
class xPDOManager extends \xPDO\Om\xPDOManager
{
    /**
     * @inheritDoc
     */
    public function createSourceContainer(
        $dsnArray = null,
        $username = null,
        $password = null,
        $containerOptions = array()
    ) {
        $created = false;
        if ($this->xpdo->getConnection(array(xPDO::OPT_CONN_MUTABLE => true))) {
            if ($dsnArray === null) $dsnArray = xPDO::parseDSN($this->xpdo->getOption('dsn'));
            if ($username === null) $username = $this->xpdo->getOption('username', null, '');
            if ($password === null) $password = $this->xpdo->getOption('password', null, '');
            if (is_array($dsnArray) && is_string($username) && is_string($password)) {
                $sql= "CREATE DATABASE {$dsnArray['dbname']}";
                if (isset ($containerOptions['charset'])) {
                    $sql.= " ENCODING '{$containerOptions['charset']}'";
                }
                if (isset ($containerOptions['collation'])) {
                    $sql.= " LC_COLLATE = '{$containerOptions['collation']}'";
                }
                $sql .= " TEMPLATE template0;";
                try {
                    $pdo = new PDO("pgsql:host={$dsnArray['host']};dbname=postgres", $username, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
                    $result = $pdo->exec($sql);
                    if ($result !== false) {
                        $created = true;
                    } else {
                        $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not create source container:\n{$sql}\nresult = " . var_export($result, true));
                    }
                } catch (PDOException $pe) {
                    $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not connect to database server: " . $pe->getMessage());
                } catch (Exception $e) {
                    $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not create source container: " . $e->getMessage());
                }
            }
        } else {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not get writable connection", '', __METHOD__, __FILE__, __LINE__);
        }

        return $created;
    }

    /**
     * @inheritDoc
     */
    public function removeSourceContainer($dsnArray = null, $username = null, $password = null)
    {
        $removed= false;
        if ($this->xpdo->getConnection(array(xPDO::OPT_CONN_MUTABLE => true))) {
            if ($dsnArray === null) $dsnArray = xPDO::parseDSN($this->xpdo->getOption('dsn'));
            if ($username === null) $username = $this->xpdo->getOption('username', null, '');
            if ($password === null) $password = $this->xpdo->getOption('password', null, '');
            if (is_array($dsnArray) && is_string($username) && is_string($password)) {
                $sql= 'DROP DATABASE IF EXISTS ' . $this->xpdo->escape($dsnArray['dbname']);
                try {
                    $pdo = new PDO("pgsql:host={$dsnArray['host']};dbname=postgres", $username, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

                    /* Disallow connections to the database */
                    if ($this->xpdo->exec("update pg_database set datallowconn = 'false' where datname = '{$dsnArray['dbname']}'")) {
                        $this->xpdo->log(xPDO::LOG_LEVEL_DEBUG, "Updated database to prevent connections to it");
                    }
                    /* Force kill existing connections to the database */
                    if (version_compare($this->xpdo->getAttribute(PDO::ATTR_SERVER_VERSION), '9.2', '>=')) {
                        $procpid = 'pid';
                    } else {
                        $procpid = 'procpid';
                    }
                    if ($this->xpdo->exec("SELECT pg_terminate_backend(pg_stat_activity.{$procpid}) FROM pg_stat_activity WHERE pg_stat_activity.datname = '{$dsnArray['dbname']}'")) {
                        $this->xpdo->log(xPDO::LOG_LEVEL_DEBUG, "All connections to database dropped");
                    }

                    $result = $pdo->exec($sql);
                    if ($result !== false) {
                        $removed = true;
                    } else {
                        $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not remove source container:\n{$sql}\nresult = " . var_export($result, true));
                    }
                    $pdo = null;
                } catch (PDOException $pe) {
                    $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not connect to database server: " . $pe->getMessage());
                } catch (Exception $e) {
                    $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not remove source container: " . $e->getMessage());
                }
            }
        } else {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not get writable connection", '', __METHOD__, __FILE__, __LINE__);
        }

        return $removed;
    }

    /**
     * @inheritDoc
     */
    public function createObjectContainer($className)
    {
        $created= false;
        if ($this->xpdo->getConnection(array(xPDO::OPT_CONN_MUTABLE => true))) {
            $instance= $this->xpdo->newObject($className);
            if ($instance) {
                $tableName= $this->xpdo->getTableName($className);
                $existsStmt = $this->xpdo->query("SELECT COUNT(*) FROM {$tableName}");
                if ($existsStmt && $existsStmt->fetchAll()) {
                    return true;
                }
                $tableMeta= $this->xpdo->getTableMeta($className);
                $sql= 'CREATE TABLE ' . $tableName . ' (';
                $fieldMeta = $this->xpdo->getFieldMeta($className, true);
                $nativeGen = false;
                $columns = array();
                foreach ($fieldMeta as $key => $meta) {
                    $columns[] = $this->getColumnDef($className, $key, $meta);
                    if (array_key_exists('generated', $meta) && $meta['generated'] == 'native') {
                        $nativeGen = true;
                    }

                }
                $sql .= implode(', ', $columns);
                $indexes = $this->xpdo->getIndexMeta($className);
                $createIndices = array();
                $tableConstraints = array();
                if (!empty ($indexes)) {
                    foreach ($indexes as $indexkey => $indexdef) {
                        $indexkey = $this->xpdo->literal($instance->_table) . '_' . $indexkey;
                        $indexType = ($indexdef['primary'] ? 'PRIMARY KEY' : ($indexdef['unique'] ? 'UNIQUE' : 'INDEX'));
                        $indexset = $this->getIndexDef($className, $indexkey, $indexdef);
                        switch ($indexType) {
                            case 'INDEX':
                                $createIndices[$indexkey] = "CREATE INDEX {$this->xpdo->escape($indexkey)} ON {$tableName} ({$indexset})";
                                break;
                            case 'PRIMARY KEY':
                            case 'UNIQUE':
                            default:
                                $tableConstraints[]= "CONSTRAINT {$this->xpdo->escape($indexkey)} {$indexType} ({$indexset})";
                                break;
                        }
                    }
                }
                if (!empty($tableConstraints)) {
                    $sql .= ', ' . implode(', ', $tableConstraints);
                }
                $sql .= ")";
                $created= $this->xpdo->exec($sql);
                if ($created === false && $this->xpdo->errorCode() !== '' && $this->xpdo->errorCode() !== PDO::ERR_NONE) {
                    $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Could not create table ' . $tableName . "\nSQL: {$sql}\nERROR: " . print_r($this->xpdo->errorInfo(), true));
                } else {
                    $created= true;
                    $this->xpdo->log(xPDO::LOG_LEVEL_INFO, 'Created table ' . $tableName . "\nSQL: {$sql}\n");
                }
                if ($created === true && !empty($createIndices)) {
                    foreach ($createIndices as $createIndexKey => $createIndex) {
                        $indexCreated = $this->xpdo->exec($createIndex);
                        if ($indexCreated === false && $this->xpdo->errorCode() !== '' && $this->xpdo->errorCode() !== PDO::ERR_NONE) {
                            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not create index {$createIndexKey}: {$createIndex} " . print_r($this->xpdo->errorInfo(), true));
                        } else {
                            $this->xpdo->log(xPDO::LOG_LEVEL_INFO, "Created index {$createIndexKey} on {$tableName}: {$createIndex}");
                        }
                    }
                }
            }
        } else {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not get writable connection", '', __METHOD__, __FILE__, __LINE__);
        }

        return $created;
    }

    /**
     * @inheritDoc
     */
    public function alterObjectContainer($className, array $options = array())
    {
        if ($this->xpdo->getConnection(array(xPDO::OPT_CONN_MUTABLE => true))) {
            // TODO: Implement alterObjectContainer() method.
        } else {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not get writable connection", '', __METHOD__, __FILE__, __LINE__);
        }
    }

    /**
     * @inheritDoc
     */
    public function removeObjectContainer($className)
    {
        $removed= false;
        if ($this->xpdo->getConnection(array(xPDO::OPT_CONN_MUTABLE => true))) {
            $instance= $this->xpdo->newObject($className);
            if ($instance) {
                $sql= 'DROP TABLE ' . $this->xpdo->getTableName($className);
                $removed= $this->xpdo->exec($sql);
                if ($removed === false && $this->xpdo->errorCode() !== '' && $this->xpdo->errorCode() !== PDO::ERR_NONE) {
                    $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Could not drop table ' . $className . "\nSQL: {$sql}\nERROR: " . print_r($this->xpdo->pdo->errorInfo(), true));
                } else {
                    $removed= true;
                    $this->xpdo->log(xPDO::LOG_LEVEL_INFO, 'Dropped table ' . $className . "\nSQL: {$sql}\n");
                }
            }
        } else {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not get writable connection", '', __METHOD__, __FILE__, __LINE__);
        }
        return $removed;
    }

    /**
     * @inheritDoc
     */
    public function addField($class, $name, array $options = array())
    {
        $result = false;
        if ($this->xpdo->getConnection(array(xPDO::OPT_CONN_MUTABLE => true))) {
            $className = $this->xpdo->loadClass($class);
            if ($className) {
                $meta = $this->xpdo->getFieldMeta($className, true);
                if (is_array($meta) && array_key_exists($name, $meta)) {
                    $colDef = $this->getColumnDef($className, $name, $meta[$name]);
                    if (!empty($colDef)) {
                        $sql = "ALTER TABLE {$this->xpdo->getTableName($className)} ADD {$colDef}";
                        if ($this->xpdo->exec($sql) !== false) {
                            $result = true;
                        } else {
                            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Error adding field {$class}->{$name}: " . print_r($this->xpdo->errorInfo(), true), '', __METHOD__, __FILE__, __LINE__);
                        }
                    } else {
                        $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Error adding field {$class}->{$name}: Could not get column definition");
                    }
                } else {
                    $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Error adding field {$class}->{$name}: No metadata defined");
                }
            }
        } else {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not get writable connection", '', __METHOD__, __FILE__, __LINE__);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function alterField($class, $name, array $options = array())
    {
        $result = false;
        if ($this->xpdo->getConnection(array(xPDO::OPT_CONN_MUTABLE => true))) {
            $className = $this->xpdo->loadClass($class);
            if ($className) {
                $meta = $this->xpdo->getFieldMeta($className, true);
                if (is_array($meta) && array_key_exists($name, $meta)) {
                    $colDef = $this->getColumnDef($className, $name, $meta[$name]);
                    if (!empty($colDef)) {
                        $sql = "ALTER TABLE {$this->xpdo->getTableName($className)} MODIFY ({$colDef})";
                        if ($this->xpdo->exec($sql) !== false) {
                            $result = true;
                        } else {
                            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Error altering field {$class}->{$name}: " . print_r($this->xpdo->errorInfo(), true), '', __METHOD__, __FILE__, __LINE__);
                        }
                    } else {
                        $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Error altering field {$class}->{$name}: Could not get column definition");
                    }
                } else {
                    $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Error altering field {$class}->{$name}: No metadata defined");
                }
            }
        } else {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not get writable connection", '', __METHOD__, __FILE__, __LINE__);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function removeField($class, $name, array $options = array())
    {
        $result = false;
        if ($this->xpdo->getConnection(array(xPDO::OPT_CONN_MUTABLE => true))) {
            $className = $this->xpdo->loadClass($class);
            if ($className) {
                $sql = "ALTER TABLE {$this->xpdo->getTableName($className)} DROP COLUMN {$this->xpdo->escape($name)}";
                $result = $this->xpdo->exec($sql);
                if ($result !== false || (!$result && $this->xpdo->errorCode() === '00000')) {
                    $result = true;
                } else {
                    $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Error removing field {$class}->{$name}: " . print_r($this->xpdo->errorInfo(), true), '', __METHOD__, __FILE__, __LINE__);
                }
            }
        } else {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not get writable connection", '', __METHOD__, __FILE__, __LINE__);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function addIndex($class, $name, array $options = array())
    {
        $result = false;
        if ($this->xpdo->getConnection(array(xPDO::OPT_CONN_MUTABLE => true))) {
            $className = $this->xpdo->loadClass($class);
            if ($className) {
                $meta = $this->xpdo->getIndexMeta($className);
                if (is_array($meta) && array_key_exists($name, $meta)) {
                    $tableName = $this->xpdo->getTableName($className);
                    $indexkey = $this->xpdo->escape($this->xpdo->literal($tableName) . '_' . $indexkey);
                    $idxDef = $this->getIndexDef($className, $name, $meta[$name]);
                    if (!empty($idxDef)) {
                        $indexType = ($meta[$name]['primary'] ? 'PRIMARY KEY' : ($meta[$name]['unique'] ? 'UNIQUE' : 'INDEX'));
                        switch ($indexType) {
                            case 'PRIMARY KEY':
                            case 'UNIQUE':
                                $sql = "ALTER TABLE {$this->xpdo->getTableName($className)} ADD CONSTRAINT {$indexkey} {$indexType} ({$idxDef})";
                                break;
                            default:
                                $sql = "CREATE {$indexType}  {$indexkey} ON {$tableName} ({$idxDef})";
                                break;
                        }
                        if ($this->xpdo->exec($sql) !== false) {
                            $result = true;
                        } else {
                            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Error adding index {$name} to {$class}: " . print_r($this->xpdo->errorInfo(), true), '', __METHOD__, __FILE__, __LINE__);
                        }
                    } else {
                        $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Error adding index {$name} to {$class}: Could not get index definition");
                    }
                } else {
                    $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Error adding index {$name} to {$class}: No metadata defined");
                }
            }
        } else {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not get writable connection", '', __METHOD__, __FILE__, __LINE__);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function removeIndex($class, $name, array $options = array())
    {
        $result = false;
        if ($this->xpdo->getConnection(array(xPDO::OPT_CONN_MUTABLE => true))) {
            $className = $this->xpdo->loadClass($class);
            if ($className) {
                $tableName = $this->xpdo->getTableName($className);
                $indexkey = $this->xpdo->escape($this->xpdo->literal($tableName) . '_' . $indexkey);
                $indexType = (isset($options['type']) && in_array($options['type'], array('PRIMARY KEY', 'UNIQUE', 'INDEX', 'FULLTEXT')) ? $options['type'] : 'INDEX');
                switch ($indexType) {
                    case 'PRIMARY KEY':
                    case 'UNIQUE':
                        $sql = "ALTER TABLE {$tableName} DROP CONSTRAINT {$indexkey}";
                        break;
                    default:
                        $sql = "DROP INDEX {$indexkey} ON {$tableName}";
                        break;
                }
                $result = $this->xpdo->exec($sql);
                if ($result !== false || (!$result && $this->xpdo->errorCode() === '00000')) {
                    $result = true;
                } else {
                    $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Error removing index {$name} from {$class}: " . print_r($this->xpdo->errorInfo(), true), '', __METHOD__, __FILE__, __LINE__);
                }
            }
        } else {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not get writable connection", '', __METHOD__, __FILE__, __LINE__);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function addConstraint($class, $name, array $options = array())
    {
        if ($this->xpdo->getConnection(array(xPDO::OPT_CONN_MUTABLE => true))) {
            // TODO: Implement addConstraint() method.
        } else {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not get writable connection", '', __METHOD__, __FILE__, __LINE__);
        }
    }

    /**
     * @inheritDoc
     */
    public function removeConstraint($class, $name, array $options = array())
    {
        $result = false;
        if ($this->xpdo->getConnection(array(xPDO::OPT_CONN_MUTABLE => true))) {
            $className = $this->xpdo->loadClass($class);
            if ($className) {
                $sql = "ALTER TABLE {$this->xpdo->getTableName($className)} DROP CONSTRAINT {$this->xpdo->escape($name)}";
                $result = $this->xpdo->exec($sql);
                if ($result !== false || (!$result && $this->xpdo->errorCode() === '00000')) {
                    $result = true;
                } else {
                    $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Error removing field {$class}->{$name}: " . print_r($this->xpdo->errorInfo(), true), '', __METHOD__, __FILE__, __LINE__);
                }
            }
        } else {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not get writable connection", '', __METHOD__, __FILE__, __LINE__);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    protected function getColumnDef($class, $name, $meta, array $options = array())
    {
        $pk= $this->xpdo->getPK($class);
        $pktype= $this->xpdo->getPKType($class);
        $dbtype= strtoupper($meta['dbtype']);
        $precision= (isset ($meta['precision']) && !in_array($this->xpdo->driver->getPHPType($dbtype), array('bit', 'date', 'datetime', 'time', 'timestamp'))) ? '(' . $meta['precision'] . ')' : '';
        if (isset ($meta['generated']) && $meta['generated'] == 'native') {
            $dbtype= 'SERIAL';
            $null= '';
        }

        $notNull= !isset ($meta['null']) ? false : ($meta['null'] === 'false' || empty($meta['null']));
        $null= $notNull ? ' NOT NULL' : ' NULL';
        $extra = '';
        if (empty ($extra) && isset ($meta['extra'])) {
            $extra= ' ' . $meta['extra'];
        }
        $default= '';
        if (array_key_exists('default', $meta)) {
            $defaultVal= $meta['default'];
            if ($defaultVal === null || strtoupper($defaultVal) === 'NULL' || in_array($this->xpdo->driver->getPHPType($dbtype), array('integer', 'float', 'bit')) || (in_array($this->xpdo->driver->getPHPType($dbtype), array('datetime', 'date', 'timestamp', 'time')) && in_array($defaultVal, array_merge($this->xpdo->driver->_currentTimestamps, $this->xpdo->driver->_currentDates, $this->xpdo->driver->_currentTimes)))) {
                $default= ' DEFAULT ' . $defaultVal;
            } else {
                $default= ' DEFAULT \'' . $defaultVal . '\'';
            }
        }
        $result = $this->xpdo->escape($name) . ' ' . $dbtype . $precision . $default . $null . $extra;

        return $result;
    }

    /**
     * @inheritDoc
     */
    protected function getIndexDef($class, $name, $meta, array $options = array())
    {
        $result = '';
        $index = isset($meta['columns']) ? $meta['columns'] : null;
        if (is_array($index)) {
            $indexset= array ();
            foreach ($index as $indexmember => $indexmemberdetails) {
                $indexMemberDetails = $this->xpdo->escape($indexmember);
                $indexset[]= $indexMemberDetails;
            }
            $result= implode(',', $indexset);
        }

        return $result;
    }
}
