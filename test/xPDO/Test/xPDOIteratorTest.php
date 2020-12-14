<?php
/*
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Test;


use xPDO\TestCase;
use xPDO\xPDO;

class xPDOIteratorTest extends TestCase
{
    /**
     * @before
     */
    public function setUpFixtures()
    {
        parent::setUpFixtures();

        $this->xpdo->getManager()->createObjectContainer('xPDO\Test\Sample\SecureItem');

        $i = 1;
        while ($i < 11) {
            if (!$this->xpdo->newObject('xPDO\Test\Sample\SecureItem', ['name' => uniqid('', true), 'public' => $i % 4 == 0 ? false : true])->save()) {
                $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Could not save SecureItem!");
            }

            $i++;
        }
    }

    /**
     * @after
     */
    public function tearDownFixtures()
    {
        $this->xpdo->manager->removeObjectContainer('xPDO\Test\Sample\SecureItem');

        parent::tearDownFixtures();
    }

    public function testIteratorDoesNotFailIfRowExistsButObjectCanNotBeLoaded()
    {
        $count = 0;
        foreach ($this->xpdo->getIterator('xPDO\Test\Sample\SecureItem') as $item) {
            $count++;
        }

        $this->assertEquals(8, $count, "xPDOIterator only iterated {$count} of an expected 8 SecureItems.");
    }
}
