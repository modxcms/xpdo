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

/**
 * Provides mysql driver abstraction for an xPDO instance.
 *
 * This is baseline metadata and methods used throughout the framework.  xPDODriver
 * class implementations are specific to a PDO driver and this instance is
 * implemented for mysql.
 *
 * @package xPDO\Om\mysql
 */
class xPDODriver extends \xPDO\Om\xPDODriver {
    public $quoteChar = "'";
    public $escapeOpenChar = '`';
    public $escapeCloseChar = '`';
    public $_currentTimestamps= array (
        'CURRENT_TIMESTAMP',
        'CURRENT_TIMESTAMP()',
        'NOW()',
        'LOCALTIME',
        'LOCALTIME()',
        'LOCALTIMESTAMP',
        'LOCALTIMESTAMP()',
        'SYSDATE()'
    );
    public $_currentDates= array (
        'CURDATE()',
        'CURRENT_DATE',
        'CURRENT_DATE()'
    );
    public $_currentTimes= array (
        'CURTIME()',
        'CURRENT_TIME',
        'CURRENT_TIME()'
    );

    /**
     * Get a mysql xPDODriver instance.
     *
     * @param xPDO &$xpdo A reference to a specific xPDO instance.
     */
    function __construct(xPDO &$xpdo) {
        parent :: __construct($xpdo);
        $this->dbtypes['integer']= array('/INT/i');
        $this->dbtypes['boolean']= array('/^BOOL/i');
        $this->dbtypes['float']= array('/^DEC/i','/^NUMERIC$/i','/^FLOAT$/i','/^DOUBLE/i','/^REAL/i');
        $this->dbtypes['string']= array('/CHAR/i','/TEXT/i','/^ENUM$/i','/^SET$/i','/^TIME$/i','/^YEAR$/i');
        $this->dbtypes['timestamp']= array('/^TIMESTAMP$/i');
        $this->dbtypes['datetime']= array('/^DATETIME$/i');
        $this->dbtypes['date']= array('/^DATE$/i');
        $this->dbtypes['binary']= array('/BINARY/i','/BLOB/i');
        $this->dbtypes['bit']= array('/^BIT$/i');
    }

    public function lastInsertId($className = null, $column = null) {
        return $this->xpdo->pdo->lastInsertId();
    }
}
