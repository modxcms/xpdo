<?php
/**
 * This file is part of the xpdo package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Om\sqlsrv;

use xPDO\xPDO;

/**
 * Provides sqlsrv driver abstraction for an xPDO instance.
 *
 * This is baseline metadata and methods used throughout the framework.  xPDODriver
 * class implementations are specific to a PDO driver and this instance is
 * implemented for sqlsrv.
 *
 * @package xPDO\Om\sqlsrv
 */
class xPDODriver extends \xPDO\Om\xPDODriver {
    public $quoteChar = "'";
    public $escapeOpenChar = '[';
    public $escapeCloseChar = ']';
    public $_currentTimestamps= array(
        "CURRENT_TIMESTAMP",
        "GETDATE()"
    );
    public $_currentDates= array(
        "CURRENT_DATE"
    );
    public $_currentTimes= array(
        "CURRENT_TIME"
    );

    /**
     * Get a sqlsrv xPDODriver instance.
     *
     * @param xPDO &$xpdo A reference to a specific xPDO instance.
     */
    function __construct(xPDO &$xpdo) {
        parent :: __construct($xpdo);
        $this->dbtypes['integer']= array('/INT$/i');
        $this->dbtypes['float']= array('/^DEC/i','/^NUMERIC$/i','/^FLOAT$/i','/^REAL$/i','/MONEY$/i');
        $this->dbtypes['string']= array('/CHAR$/i','/TEXT$/i');
        $this->dbtypes['date']= array('/^DATE$/i');
        $this->dbtypes['datetime']= array('/DATETIME/i');
        $this->dbtypes['time']= array('/^TIME$/i');
        $this->dbtypes['binary']= array('/BINARY$/i','/^IMAGE$/i');
        $this->dbtypes['bit']= array('/^BIT$/i');
    }
}
