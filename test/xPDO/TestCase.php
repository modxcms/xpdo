<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var xPDO A static xPDO fixture.
     */
    public static $fixture = null;
    /**
     * @var array A static configuration array.
     */
    public static $properties = array();

    /**
     * @var xPDO An xPDO instance for this TestCase.
     */
    public $xpdo = null;

    /**
     * Setup static properties when loading the test cases.
     */
    public static function setUpBeforeClass()
    {
        $properties = array();
        include(__DIR__ . '/../properties.inc.php');
        self::$properties = $properties;
    }

    /**
     * Grab a persistent instance of the xPDO class to share sample model data
     * across multiple tests and test suites.
     *
     * @param bool $new Indicate if a new singleton should be created
     *
     * @return xPDO An xPDO object instance.
     */
    public static function &getInstance($new = false)
    {
        if ($new || !is_object(self::$fixture)) {
            $driver = self::$properties['xpdo_driver'];
            $xpdo = xPDO::getInstance(null, self::$properties["{$driver}_array_options"]);
            if (is_object($xpdo)) {
                $logLevel = array_key_exists('logLevel', self::$properties)
                    ? self::$properties['logLevel']
                    : xPDO::LOG_LEVEL_WARN;
                $logTarget = array_key_exists('logTarget', self::$properties)
                    ? self::$properties['logTarget']
                    : (php_sapi_name() === 'cli' ? 'ECHO' : 'HTML');
                $xpdo->setLogLevel($logLevel);
                $xpdo->setLogTarget($logTarget);
                self::$fixture = $xpdo;
            }
        }
        return self::$fixture;
    }

    /**
     * Set up the xPDO fixture for each test case.
     */
    protected function setUp()
    {
        $this->xpdo = self::getInstance();
    }

    /**
     * Tear down the xPDO fixture after each test case.
     */
    protected function tearDown()
    {
        $this->xpdo = null;
    }
}
