<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Legacy;

use xPDO\xPDO;
use Yoast\PHPUnitPolyfills\TestCases\XTestCase;

abstract class TestCase extends XTestCase
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
     *
     * @beforeClass
     */
    public static function setUpFixturesBeforeClass()
    {
        self::$properties = include(__DIR__ . '/../../properties.inc.php');
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
     *
     * @before
     */
    public function setUpFixtures()
    {
        $this->xpdo = self::getInstance();
        $this->xpdo->setPackage('sample', self::$properties['xpdo_test_path'] . 'model/');
    }

    /**
     * Tear down the xPDO fixture after each test case.
     *
     * @after
     */
    public function tearDownFixtures()
    {
        $this->xpdo = null;
    }
}
