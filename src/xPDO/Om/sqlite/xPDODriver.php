<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Om\sqlite;

use xPDO\xPDO;

/**
 * Provides sqlite driver abstraction for an xPDO instance.
 *
 * This is baseline metadata and methods used throughout the framework.  xPDODriver
 * class implementations are specific to a PDO driver and this instance is
 * implemented for sqlite.
 *
 * @package xPDO\Om\sqlite
 */
class xPDODriver extends \xPDO\Om\xPDODriver {
    public $quoteChar = "'";
    public $escapeOpenChar = '"';
    public $escapeCloseChar = '"';
    public $_currentTimestamps= array(
        "CURRENT_TIMESTAMP"
    );
    public $_currentDates= array(
        "CURRENT_DATE"
    );
    public $_currentTimes= array(
        "CURRENT_TIME"
    );

    /**
     * Get a sqlite xPDODriver instance.
     *
     * @param xPDO &$xpdo A reference to a specific xPDO instance.
     */
    function __construct(xPDO &$xpdo) {
        parent :: __construct($xpdo);
        $this->dbtypes['integer']= array('/INT/i');
        $this->dbtypes['string']= array('/CHAR/i','/CLOB/i','/TEXT/i', '/ENUM/i');
        $this->dbtypes['float']= array('/REAL/i','/FLOA/i','/DOUB/i');
        $this->dbtypes['datetime']= array('/TIMESTAMP/i','/DATE/i');
        $this->dbtypes['binary']= array('/BLOB/i');
    }
}
