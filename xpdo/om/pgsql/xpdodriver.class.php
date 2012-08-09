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
 * The pgsql implementation of the xPDODriver class.
 *
 * @package xpdo
 * @subpackage om.pgsql
 */

/**
 * Include the parent {@link xPDODriver} class.
 */
require_once (dirname(dirname(__FILE__)) . '/xpdodriver.class.php');

/**
 * Provides PostgreSQL driver abstraction for an xPDO instance.
 *
 * This is baseline metadata and methods used throughout the framework.  xPDODriver 
 * class implementations are specific to a PDO driver and this instance is 
 * implemented for postgresql.
 *
 * @package xpdo
 * @subpackage om.pgsql
 */
class xPDODriver_pgsql extends xPDODriver {
    public $quoteChar = "'";
    public $escapeOpenChar = '"';
    public $escapeCloseChar = '"';
    public $_currentTimestamps= array(
        "CURRENT_TIMESTAMP",
        "CLOCK_TIMESTAMP()",
        "LOCALTIMESTAMP",
        "NOW()",
        "TRANSACTION_TIMESTAMP()",
        "STATEMENT_TIMESTAMP"
    );
    public $_currentDates= array(
        "CURRENT_DATE"
    );
    public $_currentTimes= array(
        "CURRENT_TIME",
        'LOCALTIME'
    );

    /**
     * Get a pgsql xPDODriver instance.
     *
     * @param xPDO &$xpdo A reference to a specific xPDO instance.
     */
    function __construct(xPDO &$xpdo) {
        parent :: __construct($xpdo);
        $this->dbtypes['integer']= array('/INT/i', '/SERIAL$/i');
        $this->dbtypes['boolean']= array('/^BOOLEAN$/i');
        $this->dbtypes['float']= array('/^DECIMAL$/i','/^NUMERIC$/i','/^REAL/i','/^DOUBLE/i','/^REAL/i');
        $this->dbtypes['string']= array('/CHAR/i','/^TEXT$/i','/^ENUM$/i', '/^CIDR$/i', '/^INET$/i', '/^MACADDR$/i');
        $this->dbtypes['timestamp']= array('/^TIMESTAMP$/i');
        $this->dbtypes['date']= array('/^DATE$/i');
        $this->dbtypes['time']= array('/^TIME$/i');
        $this->dbtypes['binary']= array('/BYTEA/i');
        $this->dbtypes['bit']= array('/^BIT/i');
    }
    
    public function lastInsertId($className = null, $column = null) {
        $return = false;
        $max = 0;
        if ($className) {
            if (!$column) {
                $column = $this->xpdo->getPK($className);
            }
            $tableName = $this->xpdo->literal($this->xpdo->getTableName($className));
            $sql = "SELECT currval('{$tableName}_{$column}_seq')";
            $seqStmt = $this->xpdo->query($sql);
            if ($sequence = $seqStmt->fetchColumn()) {
                $return = intval($sequence);
            }
        }
        return $return;
    }
}
