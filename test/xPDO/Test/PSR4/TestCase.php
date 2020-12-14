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
    /**
     * @before
     */
    public function setUpFixtures()
    {
        $this->xpdo = self::getInstance(true);
        $this->xpdo->setPackage('xPDO\Test\Sample', self::$properties['xpdo_test_path'] . 'model/PSR4/', null, 'xPDO\\Test\\');
    }
}
