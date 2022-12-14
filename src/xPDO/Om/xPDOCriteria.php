<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Om;

use xPDO\xPDO;

/**
 * Encapsulates a SQL query into a PDOStatement with a set of bindings.
 *
 * @package xPDO\Om
 */
class xPDOCriteria {
    public $xpdo= null;
    public $sql= '';
    public $stmt= null;
    public $bindings= array ();
    public $cacheFlag= false;

    /**
     * The constructor for a new xPDOCriteria instance.
     *
     * The constructor optionally prepares provided SQL and/or parameter
     * bindings.  Setting the bindings via the constructor or with the {@link
     * xPDOCriteria::bind()} function allows you to make use of the data object
     * caching layer.
     *
     * The statement will not be prepared immediately if the cacheFlag value is
     * true or a positive integer, in order to allow the result to be found in
     * the cache before being queried from an actual database connection.
     *
     * @param xPDO &$xpdo An xPDO instance that will control this criteria.
     * @param string $sql The SQL statement.
     * @param array $bindings Bindings to bind to the criteria.
     * @param boolean|integer $cacheFlag Indicates if the result set from the
     * criteria is to be cached (true|false) or optionally a TTL in seconds.
     * @return xPDOCriteria
     */
    public function __construct(& $xpdo, $sql= '', $bindings= array (), $cacheFlag= false) {
        $this->xpdo= & $xpdo;
        $this->cacheFlag= $cacheFlag;
        if (is_string($sql) && !empty ($sql)) {
            $this->sql= $sql;
            if ($cacheFlag === false || $cacheFlag < 0) {
                $this->stmt= $xpdo->prepare($sql);
            }
            if (!empty ($bindings)) {
                $this->bind($bindings, true, $cacheFlag);
            }
        }
    }

    /**
     * Binds an array of key/value pairs to the xPDOCriteria prepared statement.
     *
     * Use this method to bind parameters in a way that makes it possible to
     * cache results of previous executions of the criteria or compare the
     * criteria to other individual or collections of criteria.
     *
     * @param array $bindings Bindings to merge with any existing bindings
     * defined for this xPDOCriteria instance.  Bindings can be simple
     * associative array of key-value pairs or the value for each key can
     * contain elements titled value, type, and length corresponding to the
     * appropriate parameters in the PDOStatement::bindValue() and
     * PDOStatement::bindParam() functions.
     * @param boolean $byValue Determines if the $bindings are to be bound as
     * parameters (by variable reference, the default behavior) or by direct
     * value (if true).
     * @param boolean|integer $cacheFlag The cacheFlag indicates the cache state
     * of the xPDOCriteria object and can be absolutely off (false), absolutely
     * on (true), or an integer indicating the number of seconds the result will
     * live in the cache.
     */
    public function bind($bindings= array (), $byValue= true, $cacheFlag= false) {
        if (!empty ($bindings)) {
            $this->bindings= array_merge($this->bindings, $bindings);
        }
        if (is_object($this->stmt) && $this->stmt && !empty ($this->bindings)) {
            foreach ($this->bindings as $key => $val) {
                if (is_array($val)) {
                    $type= isset ($val['type']) ? $val['type'] : \PDO::PARAM_STR;
                    $length= isset ($val['length']) ? $val['length'] : 0;
                    $value= & $val['value'];
                } else {
                    $value= & $val;
                    $type= \PDO::PARAM_STR;
                    $length= 0;
                }
                if (is_int($key)) $key= $key + 1;
                if ($byValue) {
                    $this->stmt->bindValue($key, $value, $type);
                } else {
                    $this->stmt->bindParam($key, $value, $type, $length);
                }
            }
        }
        $this->cacheFlag= $cacheFlag === null ? $this->cacheFlag : $cacheFlag;
    }

    /**
     * Compares to see if two xPDOCriteria instances are the same.
     *
     * @param object $obj A xPDOCriteria object to compare to this one.
     * @return boolean true if they are both equal is SQL and bindings, otherwise
     * false.
     */
    public function equals($obj) {
        return (is_object($obj) && $obj instanceof xPDOCriteria && $this->sql === $obj->sql && !array_diff_assoc($this->bindings, $obj->bindings));
    }

    /**
     * Prepares the sql and bindings of this instance into a PDOStatement.
     *
     * The {@link xPDOCriteria::$sql} attribute must be set in order to prepare
     * the statement. You can also pass bindings directly to this function and
     * they will be run through {@link xPDOCriteria::bind()} if the statement
     * is successfully prepared.
     *
     * If the {@link xPDOCriteria::$stmt} already exists, it is simply returned.
     *
     * @param array $bindings Bindings to merge with any existing bindings
     * defined for this xPDOCriteria instance.  Bindings can be simple
     * associative array of key-value pairs or the value for each key can
     * contain elements titled value, type, and length corresponding to the
     * appropriate parameters in the PDOStatement::bindValue() and
     * PDOStatement::bindParam() functions.
     * @param boolean $byValue Determines if the $bindings are to be bound as
     * parameters (by variable reference, the default behavior) or by direct
     * value (if true).
     * @param boolean|integer $cacheFlag The cacheFlag indicates the cache state
     * of the xPDOCriteria object and can be absolutely off (false), absolutely
     * on (true), or an integer indicating the number of seconds the result will
     * live in the cache.
     * @return \PDOStatement The prepared statement, ready to execute.
     */
    public function prepare($bindings= array (), $byValue= true, $cacheFlag= null) {
        if ($this->stmt === null || !is_object($this->stmt)) {
            if (!empty ($this->sql) && $stmt= $this->xpdo->prepare($this->sql)) {
                $this->stmt= & $stmt;
                $this->bind($bindings, $byValue, $cacheFlag);
            }
        }
        return $this->stmt;
    }

    /**
     * Converts the current xPDOQuery to parsed SQL.
     *
     * @param bool $parseBindings If true, bindings are parsed locally; otherwise
     * they are left in place.
     * @return string The parsed SQL query.
     */
    public function toSQL($parseBindings = true) {
        $sql = $this->sql;
        if ($parseBindings && !empty($this->bindings)) {
            $sql = $this->xpdo->parseBindings($sql, $this->bindings);
        }
        return $sql;
    }

    public function __debugInfo()
    {
        return [
            'sql' => $this->toSQL(),
            'bindings' => $this->bindings,
        ];
    }
}
