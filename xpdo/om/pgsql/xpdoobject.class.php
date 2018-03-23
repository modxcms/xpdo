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
    public static function _save(xPDOObject &$obj, $cacheFlag= null) {
        if ($obj->isLazy()) {
            $obj->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Attempt to save lazy object: ' . print_r($obj->toArray('', true), 1));
            return false;
        }
        $result= true;
        $sql= '';
        $pk= $obj->getPrimaryKey();
        $pkn= $obj->getPK();
        $pkGenerated= false;
        if ($obj->isNew()) {
            $obj->setDirty();
        }
        if ($obj->getOption(xPDO::OPT_VALIDATE_ON_SAVE)) {
            if (!$obj->validate()) {
                return false;
            }
        }
        if (!$obj->xpdo->getConnection(array(xPDO::OPT_CONN_MUTABLE => true))) {
            $obj->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not get connection for writing data", '', __METHOD__, __FILE__, __LINE__);
            return false;
        }
        $obj->_saveRelatedObjects();
        if (!empty ($obj->_dirty)) {
            $cols= array ();
            $bindings= array ();
            $updateSql= array ();
            foreach (array_keys($obj->_dirty) as $_k) {
                if (!array_key_exists($_k, $obj->_fieldMeta)) {
                    continue;
                }
                if (isset($obj->_fieldMeta[$_k]['generated']) && $obj->_fieldMeta[$_k]['generated'] == 'native') {
                    $pkGenerated= true;
                    continue;
                }
                if ($obj->_fieldMeta[$_k]['phptype'] === 'password') {
                    $obj->_fields[$_k]= $obj->encode($obj->_fields[$_k], 'password');
                }
                $fieldType= PDO::PARAM_STR;
                $fieldValue= $obj->_fields[$_k];
                if (in_array($obj->_fieldMeta[$_k]['phptype'], array ('datetime', 'timestamp')) && !empty($obj->_fieldMeta[$_k]['attributes']) && $obj->_fieldMeta[$_k]['attributes'] == 'ON UPDATE CURRENT_TIMESTAMP') {
                    $obj->_fields[$_k]= strftime('%Y-%m-%d %H:%M:%S');
                    continue;
                }
                elseif ($fieldValue === null || $fieldValue === 'NULL') {
                    if ($obj->_new) continue;
                    $fieldType= PDO::PARAM_NULL;
                    $fieldValue= null;
                }
                elseif (in_array($obj->_fieldMeta[$_k]['phptype'], array ('timestamp', 'datetime')) && in_array($fieldValue, $obj->xpdo->driver->_currentTimestamps, true)) {
                    $obj->_fields[$_k]= strftime('%Y-%m-%d %H:%M:%S');
                    continue;
                }
                elseif (in_array($obj->_fieldMeta[$_k]['phptype'], array ('date')) && in_array($fieldValue, $obj->xpdo->driver->_currentDates, true)) {
                    $obj->_fields[$_k]= strftime('%Y-%m-%d');
                    continue;
                }
                elseif ($obj->_fieldMeta[$_k]['phptype'] == 'timestamp' && preg_match('/int/i', $obj->_fieldMeta[$_k]['dbtype'])) {
                    $fieldType= PDO::PARAM_INT;
                }
                elseif (!in_array($obj->_fieldMeta[$_k]['phptype'], array ('string','password','datetime','timestamp','date','time','array','json'))) {
                    $fieldType= PDO::PARAM_INT;
                }
                if ($obj->_new) {
                    $cols[$_k]= $obj->xpdo->escape($_k);
                    $bindings[":{$_k}"]['value']= $fieldValue;
                    $bindings[":{$_k}"]['type']= $fieldType;
                } else {
                    $bindings[":{$_k}"]['value']= $fieldValue;
                    $bindings[":{$_k}"]['type']= $fieldType;
                    $updateSql[]= $obj->xpdo->escape($_k) . " = :{$_k}";
                }
            }
            if ($obj->_new) {
                $sql= "INSERT INTO {$obj->_table} (" . implode(', ', array_values($cols)) . ") VALUES (" . implode(', ', array_keys($bindings)) . ")";
            } else {
                if ($pk && $pkn) {
                    if (is_array($pkn)) {
                        $iteration= 0;
                        $where= '';
                        foreach ($pkn as $k => $v) {
                            $vt= PDO::PARAM_INT;
                            if ($obj->_fieldMeta[$k]['phptype'] == 'string') {
                                $vt= PDO::PARAM_STR;
                            }
                            if ($iteration) {
                                $where .= " AND ";
                            }
                            $where .= $obj->xpdo->escape($k) . " = :{$k}";
                            $bindings[":{$k}"]['value']= $obj->_fields[$k];
                            $bindings[":{$k}"]['type']= $vt;
                            $iteration++;
                        }
                    } else {
                        $pkn= $obj->getPK();
                        $pkt= PDO::PARAM_INT;
                        if ($obj->_fieldMeta[$pkn]['phptype'] == 'string') {
                            $pkt= PDO::PARAM_STR;
                        }
                        $bindings[":{$pkn}"]['value']= $pk;
                        $bindings[":{$pkn}"]['type']= $pkt;
                        $where= $obj->xpdo->escape($pkn) . ' = :' . $pkn;
                    }
                    if (!empty ($updateSql)) {
                        $sql= "UPDATE {$obj->_table} SET " . implode(',', $updateSql) . " WHERE {$where}";
                    }
                }
            }
            if (!empty ($sql) && $criteria= new xPDOCriteria($obj->xpdo, $sql)) {
                if ($criteria->prepare()) {
                    if (!empty ($bindings)) {
                        $criteria->bind($bindings, true, false);
                    }
                    if ($obj->xpdo->getDebug() === true) $obj->xpdo->log(xPDO::LOG_LEVEL_DEBUG, "Executing SQL:\n{$sql}\nwith bindings:\n" . print_r($bindings, true));
                    if (!$result= $criteria->stmt->execute()) {
                        $errorInfo= $criteria->stmt->errorInfo();
                        $obj->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Error " . $criteria->stmt->errorCode() . " executing statement:\n" . $criteria->toSQL() . "\n" . print_r($errorInfo, true));
                        if (($errorInfo[1] == '1146' || $errorInfo[1] == '1') && $obj->getOption(xPDO::OPT_AUTO_CREATE_TABLES)) {
                            if ($obj->xpdo->getManager() && $obj->xpdo->manager->createObjectContainer($obj->_class) === true) {
                                if (!$result= $criteria->stmt->execute()) {
                                    $obj->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Error " . $criteria->stmt->errorCode() . " executing statement:\n{$sql}\n");
                                }
                            } else {
                                $obj->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Error " . $obj->xpdo->errorCode() . " attempting to create object container for class {$obj->_class}:\n" . print_r($obj->xpdo->errorInfo(), true));
                            }
                        }
                    }
                } else {
                    $result= false;
                }
                if ($result) {
                    if ($pkGenerated) {
                        $obj->_fields[$obj->getPK()]= $obj->xpdo->lastInsertId($obj->_class, $obj->getPK());
                    $pk= $obj->getPrimaryKey();
                    }
                    if ($pk || !$obj->getPK()) {
                        $obj->_dirty= array();
                        $obj->_validated= array();
                        $obj->_new= false;
                    }
                    $callback = $obj->getOption(xPDO::OPT_CALLBACK_ON_SAVE);
                    if ($callback && is_callable($callback)) {
                        call_user_func($callback, array('className' => $obj->_class, 'criteria' => $criteria, 'object' => $obj));
                    }
                    if ($obj->xpdo->_cacheEnabled && $pk && ($cacheFlag || ($cacheFlag === null && $obj->_cacheFlag))) {
                        $cacheKey= $obj->xpdo->newQuery($obj->_class, $pk, $cacheFlag);
                        if (is_bool($cacheFlag)) {
                            $expires= 0;
                        } else {
                            $expires= intval($cacheFlag);
                        }
                        $obj->xpdo->toCache($cacheKey, $obj, $expires, array('modified' => true));
                    }
                }
            }
        }
        $obj->_saveRelatedObjects();
        if ($result) {
            $obj->_dirty= array ();
            $obj->_validated= array ();
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
