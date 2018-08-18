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


use xPDO\xPDO;

/**
 * Provides pgsql driver abstraction for an xPDO instance.
 *
 * This is baseline metadata and methods used throughout the framework.  xPDODriver
 * class implementations are specific to a PDO driver and this instance is
 * implemented for pgsql.
 *
 * @package xPDO\Om\mysql
 */
class xPDODriver extends \xPDO\Om\xPDODriver
{
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
    function __construct(xPDO &$xpdo)
    {
        parent :: __construct($xpdo);
        $this->dbtypes['integer']= array('/INT/i', '/SERIAL$/i');
        $this->dbtypes['boolean']= array('/^BOOLEAN$/i');
        $this->dbtypes['float']= array('/^DECIMAL$/i','/^NUMERIC$/i','/^REAL/i','/^DOUBLE/i');
        $this->dbtypes['string']= array('/CHAR/i','/^TEXT$/i','/^ENUM$/i', '/^CIDR$/i', '/^INET$/i', '/^MACADDR$/i', '/^MONEY$/i');
        $this->dbtypes['timestamp']= array('/^TIMESTAMP$/i');
        $this->dbtypes['date']= array('/^DATE$/i');
        $this->dbtypes['time']= array('/^TIME$/i');
        $this->dbtypes['binary']= array('/BYTEA/i');
        $this->dbtypes['bit']= array('/^BIT/i');
        $this->dbtypes['json'] = array('/^JSON$/i');
    }
}
