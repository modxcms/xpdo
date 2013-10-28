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

abstract class TestCase extends \xPDO\TestCase
{
    /**
     * Set up the xPDO fixture for each test case.
     */
    protected function setUp()
    {
        $this->xpdo = self::getInstance();
        $this->xpdo->setPackage('sample', self::$properties['xpdo_test_path'] . 'model/');
    }
}
