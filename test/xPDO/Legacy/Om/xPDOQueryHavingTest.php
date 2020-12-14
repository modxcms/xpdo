<?php
/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Legacy\Om;

use xPDO\Om\xPDOObject;
use xPDO\Legacy\TestCase;
use xPDO\xPDO;

/**
 * Tests related to having statements.
 *
 * @package xPDO\Legacy\Om
 */
class xPDOQueryHavingTest extends TestCase
{
    /**
     * Setup dummy data for each test.
     *
     * @before
     */
    public function setUpFixtures()
    {
        parent::setUpFixtures();
        try {
            /* ensure we have clear data and identity sequences */
            $this->xpdo->getManager();
            $this->xpdo->manager->createObjectContainer('Item');

            $colors = array('red', 'green', 'yellow', 'blue');

            $r = 0;
            for ($i = 1; $i < 40; $i++) {
                $item = $this->xpdo->newObject('Item');
                $idx = str_pad($i, 2, '0', STR_PAD_LEFT);
                $item->set('name', 'item-' . $idx);
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
     *
     * @after
     */
    public function tearDownFixtures()
    {
        $this->xpdo->getManager();
        $this->xpdo->manager->removeObjectContainer('Item');
        parent::tearDownFixtures();
    }

    /**
     * Test having
     *
     * @dataProvider providerHaving
     */
    public function testHaving($having, $nameOfFirst)
    {
        try {
            $criteria = $this->xpdo->newQuery('Item');
            $criteria->groupby('id');
            $criteria->groupby('name');
            $criteria->groupby('color');
            $criteria->having($having);
            $result = $this->xpdo->getCollection('Item', $criteria);
            if (is_array($result) && !empty($result)) {
                foreach ($result as $r) {
                    /** @var xPDOObject $result */
                    $result = $r;
                    break;
                }
                $name = $result->get('name');
                $this->assertEquals($nameOfFirst, $name, 'xPDOQuery: Having clause did not return expected result, returned `' . $name . '` instead.');
            }
            else {
                throw new \Exception('xPDOQuery: Having test getCollection call did not return an array');
            }
        } catch (\Exception $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * Data provider for testHaving
     *
     * @see testHaving
     */
    public function providerHaving()
    {
        return array(
            array(array('color' => 'red'), 'item-04'),
            array(array('color' => 'green'), 'item-01'),
            array(array('id:<' => 3, 'AND:id:>' => 1), 'item-02'),
        );
    }

    /**
     * Test having with groupby statement
     *
     * @dataProvider providerHavingWithGroupBy
     */
    public function testHavingWithGroupBy($having, $nameOfFirst)
    {
        try {
            $criteria = $this->xpdo->newQuery('Item');
            $criteria->groupby('name');
            $criteria->having($having);
            $result = $this->xpdo->getCollection('Item', $criteria);
            if (is_array($result) && !empty($result)) {
                $match = null;
                foreach ($result as $r) {
                    /** @var xPDOObject $match */
                    $match = $r;
                    break;
                }
                $name = $match->get('name');
                $this->assertEquals($nameOfFirst, $name, 'xPDOQuery: Having did not return expected result, returned `' . $name . '` instead.');
            }
            else {
                throw new \Exception('xPDOQuery: Having test with groupby call did not return an array');
            }
        } catch (\Exception $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * Data provider for testHavingWithGroupBy
     *
     * @see testHavingWithGroupBy
     */
    public function providerHavingWithGroupBy()
    {
        return array(
            array(array('color' => 'red'), 'item-04'),
            array(array('color' => 'green'), 'item-01'),
            array(array('id:<' => 3, 'AND:id:>' => 1), 'item-02'),
        );
    }

    /**
     * Test having with limit statement
     *
     * @dataProvider providerHavingWithLimit
     */
    public function testHavingWithLimit($having, $limit, $start, $nameOfFirst)
    {
        try {
            $criteria = $this->xpdo->newQuery('Item');
            $criteria->groupby('id');
            $criteria->groupby('name');
            $criteria->groupby('color');
            $criteria->having($having);
            $criteria->limit($limit, $start);
            $result = $this->xpdo->getCollection('Item', $criteria);
            if (is_array($result) && !empty($result)) {
                foreach ($result as $r) {
                    /** @var xPDOObject $result */
                    $result = $r;
                    break;
                }
                $name = $result->get('name');
                $this->assertEquals($nameOfFirst, $name, 'xPDOQuery: Having did not return expected result `' . $nameOfFirst . '`, returned `' . $name . '` instead: ' . $criteria->toSql());
            }
            else {
                throw new \Exception('xPDOQuery: Having test with limit call did not return an array');
            }
        } catch (\Exception $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * Data provider for testHavingWithGroupBy
     *
     * @see testHavingWithLimit
     */
    public function providerHavingWithLimit()
    {
        return array(
            array(array('color' => 'red'), 1, 0, 'item-04'),
            array(array('color' => 'green'), 1, 0, 'item-01'),
            array(array('id:<' => 3, 'AND:id:>' => 1), 1, 0, 'item-02'),
        );
    }
}
