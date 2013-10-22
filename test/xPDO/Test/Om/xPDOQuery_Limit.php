<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Test\Om;

use xPDO\TestCase;
use xPDO\xPDO;

/**
 * Tests related to limit statements.
 *
 * @package xPDO\Test\Om
 */
class xPDOQueryLimitTest extends TestCase
{
    /**
     * Setup dummy data for each test.
     */
    public function setUp()
    {
        parent::setUp();
        try {
            /* ensure we have clear data and identity sequences */
            $this->xpdo->getManager();
            $this->xpdo->manager->createObjectContainer('Item');

            $colors = array('red', 'green', 'yellow', 'blue');

            $r = 0;
            for ($i = 1; $i < 40; $i++) {
                $item = $this->xpdo->newObject('Item');
                $idx = str_pad($i, 2, '0', STR_PAD_LEFT);
                $item->set('name', 'item-' . $i);
                $r++;
                if ($r > 3) $r = 0;
                $item->set('color', $colors[$r]);
                $item->save();
            }
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
    }

    /**
     * Clean up data when through.
     */
    public function tearDown()
    {
        $this->xpdo->getManager();
        $this->xpdo->manager->removeObjectContainer('Item');
        parent::tearDown();
    }

    /**
     * Test limit
     *
     * @dataProvider providerLimit
     *
     * @param int $limit A number to limit by
     * @param int $start The index to start on
     * @param boolean $shouldEqual If the result count should equal the limit
     */
    public function testLimit($limit, $start = 0, $shouldEqual = true)
    {
        try {
            $criteria = $this->xpdo->newQuery('Item');
            $criteria->limit($limit, $start);
            $result = $this->xpdo->getCollection('Item', $criteria);
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $success = count($result) == $limit;
        if (!$shouldEqual) $success = !$success;
        if (!$success) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, "Expected {$limit}, got " . count($result) . "; with query: " . $criteria->toSql());
        }
        $this->assertTrue($success, 'xPDOQuery: Limit clause returned more than desired ' . $limit . ' result.');
    }

    /**
     * Data provider for testLimit
     *
     * @see testLimit
     */
    public function providerLimit()
    {
        return array(
            array(1, 0, true), /* limit 1, start at 0 */
            array(2, 0, true), /* limit 2, start at 0 */
            array(50, 0, false), /* limit 50, start at 0, should not match */
            array(50, 45, false), /* limit 50, start at 45, should not match */
            array(5, 2, true), /* limit 5, start at 2 */
        );
    }

    /**
     * Test limit with groupby clause
     *
     * @dataProvider providerLimitWithGroupBy
     *
     * @param int $limit A number to limit by
     * @param int $start The index to start on
     * @param boolean $shouldEqual If the result count should equal the limit
     */
    public function testLimitWithGroupBy($limit, $start = 0, $shouldEqual = true)
    {
        try {
            $criteria = $this->xpdo->newQuery('Item');
            $criteria->groupby('color');
            $criteria->limit($limit, $start);
            $result = $this->xpdo->getCollection('Item', $criteria);
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $success = count($result) == $limit;
        if (!$shouldEqual) $success = !$success;
        $this->assertTrue($success, 'xPDOQuery: Limit clause grouped by color returned more than desired ' . $limit . ' result.');
    }

    /**
     * Data provider for testLimit
     *
     * @see testLimitWithGroupBy
     */
    public function providerLimitWithGroupBy()
    {
        return array(
            array(3, 0, true), /* limit 3, start at 0 */
        );
    }

    /**
     * Test limit with sortby clause
     *
     * @dataProvider providerLimitWithSortBy
     *
     * @param int $limit A number to limit by
     * @param int $start The index to start on
     * @param boolean $shouldEqual If the result count should equal the limit
     */
    public function testLimitWithSortBy($limit, $start = 0, $shouldEqual = true)
    {
        try {
            $criteria = $this->xpdo->newQuery('Item');
            $criteria->sortby('color', 'ASC');
            $criteria->limit($limit, $start);
            $result = $this->xpdo->getCollection('Item', $criteria);
        } catch (\Exception $e) {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
        }
        $success = count($result) == $limit;
        if (!$shouldEqual) $success = !$success;
        $this->assertTrue($success, 'xPDOQuery: Limit clause with sortby returned more than desired ' . $limit . ' result.');
    }

    /**
     * Data provider for testLimit
     *
     * @see testLimitWithSortBy
     */
    public function providerLimitWithSortBy()
    {
        return array(
            array(3, 0, true), /* limit 3, start at 0 */
            array(3, 3, true), /* limit 3, start at 3 */
        );
    }
}
