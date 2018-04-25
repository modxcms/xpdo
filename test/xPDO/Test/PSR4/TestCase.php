<?php
/*
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Test\PSR4;


class TestCase extends \xPDO\TestCase
{
    protected function setUp()
    {
        $this->xpdo = self::getInstance(true);
        $this->xpdo->setPackage('Sample', self::$properties['xpdo_test_path'] . 'model/PSR4/');
    }
}
