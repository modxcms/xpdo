<?php
/**
 * This file is part of the xpdo package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Om;

use xPDO\xPDO;

/**
 * Provides driver specific members and methods for an xPDO instance.
 *
 * These are baseline members and methods that need to be loaded every
 * time an xPDO instance makes a connection.  xPDODriver class implementations
 * are specific to a database driver and should include this base class in order
 * to extend it.
 *
 * @package xPDO\Om
 */
abstract class xPDODriver {
    /**
     * @var xPDO A reference to the XPDO instance using this manager.
     * @access public
     */
    public $xpdo= null;
    /**
     * @var array Describes the physical database types.
     */
    public $dbtypes= array ();
    /**
     * An array of DB constants/functions that represent timestamp values.
     * @var array
     */
    public $_currentTimestamps= array();
    /**
     * An array of DB constants/functions that represent date values.
     * @var array
     */
    public $_currentDates= array();
    /**
     * An array of DB constants/functions that represent time values.
     * @var array
     */
    public $_currentTimes= array();
    public $quoteChar = '';
    public $escapeOpenChar = '';
    public $escapeCloseChar = '';

    /**
     * Get an xPDODriver instance.
     *
     * @param xPDO $xpdo A reference to a specific xPDO instance.
     */
    public function __construct(xPDO &$xpdo) {
        if ($xpdo !== null && $xpdo instanceof xPDO) {
            $this->xpdo= & $xpdo;
            $this->xpdo->_quoteChar= $this->quoteChar;
            $this->xpdo->_escapeCharOpen= $this->escapeOpenChar;
            $this->xpdo->_escapeCharClose= $this->escapeCloseChar;
        }
    }

    /**
     * Gets the PHP field type based upon the specified database type.
     *
     * @access public
     * @param string $dbtype The database field type to convert.
     * @return string The associated PHP type
     */
    public function getPhpType($dbtype) {
        $phptype = 'string';
        if ($dbtype !== null) {
            foreach ($this->dbtypes as $type => $patterns) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $dbtype)) {
                        $phptype = $type;
                        break 2;
                    }
                }
            }
        }
        return $phptype;
    }
}
