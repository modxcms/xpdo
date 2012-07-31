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
 * The oci implementation of xPDOQuery.
 *
 * @package xpdo
 * @subpackage om.pgsql
 */

/** Include the base {@see xPDOQuery} class */
include_once (dirname(dirname(__FILE__)) . '/xpdoquery.class.php');

/**
 * An implementation of xPDOQuery for the pgsql database driver.
 *
 * @package xpdo
 * @subpackage om.oci
 */
class xPDOQuery_pgsql extends xPDOQuery {
    public function construct() {
        $constructed= false;
        $this->bindings= array ();
        $command= strtoupper($this->query['command']);
        $sql= $this->query['command'] . ' ';
        $limit= !empty($this->query['limit']) ? intval($this->query['limit']) : 0;
        $offset= !empty($this->query['offset']) ? intval($this->query['offset']) : 0;
        $orderBySql = '';
        
        if ($command == 'SELECT' && !empty ($this->query['sortby'])) {
            $sortby= reset($this->query['sortby']);
            $orderBySql= 'ORDER BY ';
            $orderBySql .= implode(" ", $sortby) . " ";
        }
        
        if ($command == 'SELECT' && $orderBySql == '' && !empty($limit)) {
            $pk = $this->xpdo->getPK($this->getClass());
            if ($pk) {
                if (!is_array($pk)) $pk = array($pk);
                $orderBy = array();
                foreach ($pk as $k) {
                    $orderBy[] = $this->xpdo->escape($this->getAlias()) . '.' . $this->xpdo->escape($k);
                }
                $orderBySql = "ORDER BY " . implode(', ', $orderBy) . " ";
            }
        }
        if ($command == 'SELECT') {
            $sql.= !empty($this->query['distinct']) ? $this->query['distinct'] . ' ' : '';
            $columns= array ();
            if (empty ($this->query['columns'])) {
                $this->select('*');
            }
            foreach ($this->query['columns'] as $alias => $column) {
                $ignorealias = is_int($alias);
                $escape = !preg_match('/\bAS\b/i', $column) && !preg_match('/\./', $column) && !preg_match('/\(/', $column);
                if ($escape) {
                    $column= $this->xpdo->escape(trim($column));
                } else {
                    $column= trim($column);
                }
                if (!$ignorealias) {
                    $alias = $escape ? $this->xpdo->escape($alias) : $alias;
                    $columns[]= "{$column} AS {$alias}";
                } else {
                    $columns[]= "{$column}";
                }
            }
            $sql.= implode(', ', $columns);
            $sql.= ' ';
        }
        if ($command != 'UPDATE') {
            $sql.= 'FROM ';
        }
        $tables= array ();
        foreach ($this->query['from']['tables'] as $table) {
            if ($command != 'SELECT') {
                $tables[]= $this->xpdo->escape($table['table']);
            } else {
                $tables[]= $this->xpdo->escape($table['table']) . ' ' . $this->xpdo->escape($table['alias']);
            }
        }
        $sql.= $this->query['from']['tables'] ? implode(', ', $tables) . ' ' : '';
        if (!empty ($this->query['from']['joins'])) {
            foreach ($this->query['from']['joins'] as $join) {
                $sql.= $join['type'] . ' ' . $this->xpdo->escape($join['table']) . ' ' . $this->xpdo->escape($join['alias']) . ' ';
                if (!empty ($join['conditions'])) {
                    $sql.= 'ON ';
                    $sql.= $this->buildConditionalClause($join['conditions']);
                    $sql.= ' ';
                }
            }
        }
        if ($command == 'UPDATE') {
            if (!empty($this->query['set'])) {
                reset($this->query['set']);
                $clauses = array();
                $fieldMeta = $this->xpdo->getFieldMeta($this->_class);
                $fieldAliases= $this->xpdo->getFieldAliases($this->_class);
                
                while (list($setKey, $setVal) = each($this->query['set'])) {
                    $value = $setVal['value'];
                    $type = $setVal['type'];
                    if ($value !== null && in_array($type, array(PDO::PARAM_INT, PDO::PARAM_STR))) {
                        $value = $this->xpdo->quote($value, $type);
                    } elseif ($value === null) {
                        $value = 'NULL';
                    }
                    if (!array_key_exists($setKey, $fieldMeta)) {
                        if (array_key_exists($setKey, $fieldAliases)) {
                            $setKey = $fieldAliases[$setKey];
                        } else {
                            continue;
                        }
                    }
                    $clauses[] = $this->xpdo->escape($setKey) . ' = ' . $value;
                }
                if (!empty($clauses)) {
                    $sql.= 'SET ' . implode(', ', $clauses);
                }
                unset($clauses);
            }
            $sql .= " ";
        }
        if (!empty ($this->query['where'])) {
            if ($where= $this->buildConditionalClause($this->query['where'])) {
                $sql.= 'WHERE ' . $where . ' ';
            }
        }
        if ($command == 'SELECT' && !empty ($this->query['groupby'])) {
            $groupby= reset($this->query['groupby']);
            $sql.= 'GROUP BY ';
            $sql.= $groupby['column'];
            
            while ($groupby= next($this->query['groupby'])) {
                $sql.= ", " . $groupby['column'];
            }
            $sql.= ' ';
        }
        if (!empty ($this->query['having'])) {
            $sql.= 'HAVING ';
            $sql.= $this->buildConditionalClause($this->query['having']);
            $sql.= ' ';
        }
        if ($orderBySql) {
            $sql .= $orderBySql;
        }
        if ($limit= intval($this->query['limit'])) {
            $sql.= 'LIMIT ' . $limit;
            if ($offset= intval($this->query['offset'])) $sql.= ' OFFSET ' . $offset;
        }
        $this->sql= $sql;
        return (!empty ($this->sql));
    }
}
