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
 * Contains a derivative of the xPDOObject class for pgsql.
 *
 * This file contains the base persistent object classes for pgsql, which your
 * user-defined classes will extend when implementing an xPDO object model
 * targeted at the PostgreSQL platform.
 *
 * @package xpdo
 * @subpackage om.pgsql
 */

if (!class_exists('xPDOObject')) {
    /** Include the parent {@link xPDOObject} class. */
    include_once (dirname(dirname(__FILE__)) . '/xpdoobject.class.php');
}

/**
 * Implements extensions to the base xPDOObject class for pgsql.
 *
 * {@inheritdoc}
 *
 * @package xpdo
 * @subpackage om.pgsql
 */
class xPDOObject_pgsql extends xPDOObject {
    public function save($cacheFlag= null) {
        if ($this->isLazy()) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Attempt to save lazy object: ' . print_r($this->toArray('', true), 1));
            return false;
        }
        $result= true;
        $sql= '';
        $pk= $this->getPrimaryKey();
        $pkn= $this->getPK();
        $pkGenerated= false;
        if ($this->isNew()) {
            $this->setDirty();
        }
        if ($this->getOption(xPDO::OPT_VALIDATE_ON_SAVE)) {
            if (!$this->validate()) {
                return false;
            }
        }
        if (!$this->xpdo->getConnection(array(xPDO::OPT_CONN_MUTABLE => true))) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not get connection for writing data", '', __METHOD__, __FILE__, __LINE__);
            return false;
        }
        $this->_saveRelatedObjects();
        if (!empty ($this->_dirty)) {
            $cols= array ();
            $bindings= array ();
            $updateSql= array ();
            foreach (array_keys($this->_dirty) as $_k) {
                if (!array_key_exists($_k, $this->_fieldMeta)) {
                    continue;
                }
                if (isset($this->_fieldMeta[$_k]['generated']) && $this->_fieldMeta[$_k]['generated'] == 'native') {
                    $pkGenerated= true;
                    continue;
                }
                if ($this->_fieldMeta[$_k]['phptype'] === 'password') {
                    $this->_fields[$_k]= $this->encode($this->_fields[$_k], 'password');
                }
                $fieldType= PDO::PARAM_STR;
                $fieldValue= $this->_fields[$_k];
                if (in_array($this->_fieldMeta[$_k]['phptype'], array ('datetime', 'timestamp')) && !empty($this->_fieldMeta[$_k]['attributes']) && $this->_fieldMeta[$_k]['attributes'] == 'ON UPDATE CURRENT_TIMESTAMP') {
                    $this->_fields[$_k]= strftime('%Y-%m-%d %H:%M:%S');
                    continue;
                }
                elseif ($fieldValue === null || $fieldValue === 'NULL') {
                    if ($this->_new) continue;
                    $fieldType= PDO::PARAM_NULL;
                    $fieldValue= null;
                }
                elseif (in_array($this->_fieldMeta[$_k]['phptype'], array ('timestamp', 'datetime')) && in_array($fieldValue, $this->xpdo->driver->_currentTimestamps, true)) {
                    $this->_fields[$_k]= strftime('%Y-%m-%d %H:%M:%S');
                    continue;
                }
                elseif (in_array($this->_fieldMeta[$_k]['phptype'], array ('date')) && in_array($fieldValue, $this->xpdo->driver->_currentDates, true)) {
                    $this->_fields[$_k]= strftime('%Y-%m-%d');
                    continue;
                }
                elseif ($this->_fieldMeta[$_k]['phptype'] == 'timestamp' && preg_match('/int/i', $this->_fieldMeta[$_k]['dbtype'])) {
                    $fieldType= PDO::PARAM_INT;
                }
                elseif (!in_array($this->_fieldMeta[$_k]['phptype'], array ('string','password','datetime','timestamp','date','time','array','json'))) {
                    $fieldType= PDO::PARAM_INT;
                }
                if ($this->_new) {
                    $cols[$_k]= $this->xpdo->escape($_k);
                    $bindings[":{$_k}"]['value']= $fieldValue;
                    $bindings[":{$_k}"]['type']= $fieldType;
                } else {
                    $bindings[":{$_k}"]['value']= $fieldValue;
                    $bindings[":{$_k}"]['type']= $fieldType;
                    $updateSql[]= $this->xpdo->escape($_k) . " = :{$_k}";
                }
            }
            if ($this->_new) {
                $sql= "INSERT INTO {$this->_table} (" . implode(', ', array_values($cols)) . ") VALUES (" . implode(', ', array_keys($bindings)) . ")";
            } else {
                if ($pk && $pkn) {
                    if (is_array($pkn)) {
                        $iteration= 0;
                        $where= '';
                        foreach ($pkn as $k => $v) {
                            $vt= PDO::PARAM_INT;
                            if ($this->_fieldMeta[$k]['phptype'] == 'string') {
                                $vt= PDO::PARAM_STR;
                            }
                            if ($iteration) {
                                $where .= " AND ";
                            }
                            $where .= $this->xpdo->escape($k) . " = :{$k}";
                            $bindings[":{$k}"]['value']= $this->_fields[$k];
                            $bindings[":{$k}"]['type']= $vt;
                            $iteration++;
                        }
                    } else {
                        $pkn= $this->getPK();
                        $pkt= PDO::PARAM_INT;
                        if ($this->_fieldMeta[$pkn]['phptype'] == 'string') {
                            $pkt= PDO::PARAM_STR;
                        }
                        $bindings[":{$pkn}"]['value']= $pk;
                        $bindings[":{$pkn}"]['type']= $pkt;
                        $where= $this->xpdo->escape($pkn) . ' = :' . $pkn;
                    }
                    if (!empty ($updateSql)) {
                        $sql= "UPDATE {$this->_table} SET " . implode(',', $updateSql) . " WHERE {$where}";
                    }
                }
            }
            if (!empty ($sql) && $criteria= new xPDOCriteria($this->xpdo, $sql)) {
                if ($criteria->prepare()) {
                    if (!empty ($bindings)) {
                        $criteria->bind($bindings, true, false);
                    }
                    if ($this->xpdo->getDebug() === true) $this->xpdo->log(xPDO::LOG_LEVEL_DEBUG, "Executing SQL:\n{$sql}\nwith bindings:\n" . print_r($bindings, true));
                    if (!$result= $criteria->stmt->execute()) {
                        $errorInfo= $criteria->stmt->errorInfo();
                        $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Error " . $criteria->stmt->errorCode() . " executing statement:\n" . $criteria->toSQL() . "\n" . print_r($errorInfo, true));
                        if (($errorInfo[1] == '1146' || $errorInfo[1] == '1') && $this->getOption(xPDO::OPT_AUTO_CREATE_TABLES)) {
                            if ($this->xpdo->getManager() && $this->xpdo->manager->createObjectContainer($this->_class) === true) {
                                if (!$result= $criteria->stmt->execute()) {
                                    $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Error " . $criteria->stmt->errorCode() . " executing statement:\n{$sql}\n");
                                }
                            } else {
                                $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Error " . $this->xpdo->errorCode() . " attempting to create object container for class {$this->_class}:\n" . print_r($this->xpdo->errorInfo(), true));
                            }
                        }
                    }
                } else {
                    $result= false;
                }
                if ($result) {
                    if ($pkGenerated) {
                        $this->_fields[$this->getPK()]= $this->xpdo->lastInsertId($this->_class, $this->getPK());
                    $pk= $this->getPrimaryKey();
                    }
                    if ($pk || !$this->getPK()) {
                        $this->_dirty= array();
                        $this->_validated= array();
                        $this->_new= false;
                    }
                    $callback = $this->getOption(xPDO::OPT_CALLBACK_ON_SAVE);
                    if ($callback && is_callable($callback)) {
                        call_user_func($callback, array('className' => $this->_class, 'criteria' => $criteria, 'object' => $this));
                    }
                    if ($this->xpdo->_cacheEnabled && $pk && ($cacheFlag || ($cacheFlag === null && $this->_cacheFlag))) {
                        $cacheKey= $this->xpdo->newQuery($this->_class, $pk, $cacheFlag);
                        if (is_bool($cacheFlag)) {
                            $expires= 0;
                        } else {
                            $expires= intval($cacheFlag);
                        }
                        $this->xpdo->toCache($cacheKey, $this, $expires, array('modified' => true));
                    }
                }
            }
        }
        $this->_saveRelatedObjects();
        if ($result) {
            $this->_dirty= array ();
            $this->_validated= array ();
        }
        return $result;
    }
}

/**
 * Extend this abstract class to define a class having an integer primary key.
 *
 * @package xpdo
 * @subpackage om.pgsql
 */
class xPDOSimpleObject_pgsql extends xPDOSimpleObject {}
